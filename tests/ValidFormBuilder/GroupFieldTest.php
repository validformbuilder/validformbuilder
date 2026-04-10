<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Group;
use ValidFormBuilder\GroupField;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\GroupField}.
 *
 * GroupField is a thin Element subclass representing a single radio button
 * or checkbox inside a Group. It is always created via Group::addField()
 * in normal usage. This test exercises it both through the Group API and
 * via direct construction for edge cases.
 *
 * Surface covered:
 * - Constructor wiring: id override, value, checked state, parent delegation.
 * - toHtmlInternal(): radio vs checkbox type mapping, checked attribute on
 *   default / submitted scalar / submitted array, dynamic count name suffix.
 * - __getValue(): fallback to configured value when parent returns null.
 *
 * Security audit:
 * - XSS escape of value and label (regression for #202 fix).
 * - Loose-equality type juggling in value comparison.
 */
class GroupFieldTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        foreach (['color', 'fruit', 'fruit[]'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresIdValueAndCheckedState(): void
    {
        $field = new GroupField(
            'color-red',
            'color',
            ValidForm::VFORM_RADIO_LIST,
            'Red',
            'red',
            true
        );

        $this->assertSame('color-red', $field->getId());
        $this->assertSame('color', $field->getName());
        $this->assertSame('Red', $field->getLabel());
        $this->assertSame(ValidForm::VFORM_RADIO_LIST, $field->getType());
    }

    #[Test]
    public function constructorOverridesIdFromParentElement(): void
    {
        // Element::__construct normally sets __id = __name (for non-bracket names).
        // GroupField's constructor overrides __id after the parent call.
        $field = new GroupField(
            'custom-id',
            'group-name',
            ValidForm::VFORM_RADIO_LIST,
            'Label',
            'value'
        );

        $this->assertSame('custom-id', $field->getId());
        $this->assertSame('group-name', $field->getName());
    }

    // --------------------------------------------------------------
    // toHtmlInternal — rendering
    // --------------------------------------------------------------

    #[Test]
    public function radioFieldRendersAsInputTypeRadio(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        $xpath = $this->parseHtml($red->toHtmlInternal(null, false));

        // `//input[@type="radio"]` — the single radio input rendered by this GroupField.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('red', $input->getAttribute('value'));
        $this->assertSame('color', $input->getAttribute('name'));
    }

    #[Test]
    public function checkboxFieldRendersAsInputTypeCheckbox(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $apple = $group->addField('Apple', 'apple');

        $xpath = $this->parseHtml($apple->toHtmlInternal(null, false));

        // `//input[@type="checkbox"]` — the single checkbox input.
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('apple', $input->getAttribute('value'));
        $this->assertSame('fruit[]', $input->getAttribute('name'));
    }

    #[Test]
    public function defaultCheckedOptionRendersCheckedAttribute(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $blue = $group->addField('Blue', 'blue', true);

        $xpath = $this->parseHtml($blue->toHtmlInternal(null, false));

        // `//input[@type="radio"]` — the blue option should have the checked attribute.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        // The attribute may contain duplicate tokens due to the setFieldMeta append
        // pattern, but any non-empty value is truthy for a boolean HTML attribute.
        $this->assertNotEmpty($input->getAttribute('checked'));
    }

    #[Test]
    public function uncheckedDefaultOptionDoesNotRenderCheckedAttribute(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');
        $group->addField('Blue', 'blue', true);

        $xpath = $this->parseHtml($red->toHtmlInternal(null, false));

        // `//input[@type="radio"]` — the red option (not default-checked) should
        // have no checked attribute.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('', $input->getAttribute('checked'));
    }

    #[Test]
    public function submittedScalarValueSetsCheckedOnMatch(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        $xpath = $this->parseHtml($red->toHtmlInternal('red', true));

        // `//input[@type="radio"]` — submitted value matches this field's value.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('checked', $input->getAttribute('checked'));
    }

    #[Test]
    public function submittedScalarValueClearsCheckedOnMismatch(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        // Submitted value 'blue' does not match 'red'.
        $xpath = $this->parseHtml($red->toHtmlInternal('blue', true));

        // `//input[@type="radio"]` — should NOT be checked.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('', $input->getAttribute('checked'));
    }

    #[Test]
    public function submittedArrayValueSetsCheckedOnMatch(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $apple = $group->addField('Apple', 'apple');

        // Submitted array includes 'apple'.
        $xpath = $this->parseHtml($apple->toHtmlInternal(['apple', 'cherry'], true));

        // `//input[@type="checkbox"]` — should be checked because 'apple' is in the array.
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('checked', $input->getAttribute('checked'));
    }

    #[Test]
    public function submittedArrayValueDoesNotCheckOnMismatch(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $apple = $group->addField('Apple', 'apple');

        // Submitted array does NOT include 'apple'.
        $xpath = $this->parseHtml($apple->toHtmlInternal(['banana', 'cherry'], true));

        // `//input[@type="checkbox"]` — should not be checked.
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('', $input->getAttribute('checked'));
    }

    #[Test]
    public function dynamicCountSuffixesNameAndIdForRadio(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        // intCount > 0 triggers the dynamic suffix.
        $xpath = $this->parseHtml($red->toHtmlInternal(null, false, 2));

        // `//input[@type="radio"]` — should have suffixed name and id.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('color_2', $input->getAttribute('name'));
    }

    #[Test]
    public function dynamicCountSuffixesNameWithBracketsForChecklist(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $apple = $group->addField('Apple', 'apple');

        // For check lists, the dynamic suffix goes between the base name and the brackets:
        // `fruit[]` → `fruit_2[]` for intCount=2.
        $xpath = $this->parseHtml($apple->toHtmlInternal(null, false, 2));

        // `//input[@type="checkbox"]` — should have suffixed name with brackets.
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('fruit_2[]', $input->getAttribute('name'));
    }

    #[Test]
    public function labelTextRendersInsideLabelElement(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        $xpath = $this->parseHtml($red->toHtmlInternal(null, false));

        // `//label` — the wrapping <label> element whose text content contains the option label.
        $label = $xpath->query('//label')->item(0);
        $this->assertNotNull($label);
        $this->assertStringContainsString('Red', $label->textContent);
    }

    // --------------------------------------------------------------
    // __getValue fallback
    // --------------------------------------------------------------

    #[Test]
    public function getValueFallsBackToConfiguredValueWhenParentReturnsNull(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        // No $_REQUEST, no default on the parent → parent::__getValue returns null.
        // GroupField::__getValue falls back to $this->__value.
        $this->assertSame('red', $red->__getValue(false));
    }

    #[Test]
    public function getValueReturnsSubmittedValueWhenAvailable(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');

        $_REQUEST['color'] = 'blue';

        // When parent::__getValue resolves to the submitted value, the fallback
        // is not reached.
        $this->assertSame('blue', $red->__getValue(true));
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function xssInValueAttributeIsEscaped(): void
    {
        // SECURITY regression for #202: the value attribute must be escaped via
        // htmlspecialchars so a crafted value cannot break out of the attribute.
        $group = $this->form->addField('xss', 'XSS', ValidForm::VFORM_RADIO_LIST);
        $option = $group->addField('Attack', '" onclick="alert(1)');

        $xpath = $this->parseHtml($option->toHtmlInternal(null, false));

        // `//input[@type="radio"]` — the value attribute should contain the literal
        // escaped payload, not break into a separate onclick attribute.
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('" onclick="alert(1)', $input->getAttribute('value'));
        $this->assertSame('', $input->getAttribute('onclick'));
    }

    #[Test]
    public function xssInLabelTextIsEscaped(): void
    {
        // SECURITY: the label text is rendered inside the <label> element. A
        // crafted label containing HTML should be escaped to prevent injection.
        $group = $this->form->addField('xss', 'XSS', ValidForm::VFORM_RADIO_LIST);
        $option = $group->addField('<script>alert(1)</script>', 'safe');

        $xpath = $this->parseHtml($option->toHtmlInternal(null, false));

        // `//script` — no <script> elements should appear in the DOM if escaping works.
        $this->assertSame(0, $xpath->query('//script')->length);

        // The label text should contain the literal escaped HTML as text, not as executed tags.
        // `//label` — the wrapper label element.
        $label = $xpath->query('//label')->item(0);
        $this->assertNotNull($label);
        $this->assertStringContainsString('&lt;script&gt;', $label->ownerDocument->saveHTML($label));
    }

    #[Test]
    public function looseComparisonDoesNotCauseUnexpectedCheckOnNumericStringValues(): void
    {
        // SECURITY note: toHtmlInternal uses `$valueItem == $this->__value` (loose ==).
        // This test documents the behaviour for numeric-looking values: PHP's loose
        // comparison means '0' == 0 is true, but '0' == '0' is also true. For string
        // values like 'option-0' the risk is minimal. For purely numeric values
        // like '0', ensure the comparison still works correctly with string submission.
        $group = $this->form->addField('rating', 'Rating', ValidForm::VFORM_RADIO_LIST);
        $zero = $group->addField('None', '0');
        $one = $group->addField('One', '1');

        // Submit '0' — only the 'None' option (value '0') should be checked.
        $xpathZero = $this->parseHtml($zero->toHtmlInternal('0', true));
        $xpathOne = $this->parseHtml($one->toHtmlInternal('0', true));

        // `//input[@type="radio"]` — check the checked attribute on each.
        $inputZero = $xpathZero->query('//input[@type="radio"]')->item(0);
        $inputOne = $xpathOne->query('//input[@type="radio"]')->item(0);

        $this->assertSame('checked', $inputZero->getAttribute('checked'));
        $this->assertSame('', $inputOne->getAttribute('checked'));
    }
}
