<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Collection;
use ValidFormBuilder\MultiField;
use ValidFormBuilder\StaticText;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\MultiField}.
 *
 * MultiField combines multiple fields under a single shared label (e.g.
 * first name + last name → "Full name"). It extends Base (not Element)
 * and renders children in "simple layout" mode (no individual labels).
 *
 * Surface covered:
 * - Constructor: label, meta, optional name; dynamic meta resolution.
 * - addField(): renders via ValidForm::renderField, strips dynamic meta
 *   from children, sets parent, injects hidden counter when parent dynamic.
 * - addText(): adds StaticText child.
 * - hasFields(): false when empty, true when populated.
 * - getFields(), getValue(), getType(), getId() placeholders.
 * - isDynamic(): meta-driven.
 * - isValid() / __validate(): per-child validation, stops on first failure.
 * - hasContent(): checks if any non-Hidden child has a non-empty value.
 * - setData() / getData(): custom meta-data round-trip.
 * - toHtml: wrapper div with vf__multifield class, label, child fields
 *   in simple layout, error messages, tip element.
 * - toHtml returns empty for multifield with no children.
 * - toJS: concatenates child JS output.
 *
 * Security audit:
 * - addField strips dynamic meta keys from children (prevents nested dynamic abuse).
 * - No new XSS vectors found — children render themselves with their own escaping.
 */
class MultiFieldTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        foreach (['first-name', 'last-name', 'required-field'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresLabelAndInitialisesEmptyFieldsCollection(): void
    {
        $multi = new MultiField('Full name');

        $this->assertSame('Full name', $multi->getLabel());
        $this->assertInstanceOf(Collection::class, $multi->getFields());
        $this->assertSame(0, $multi->getFields()->count());
    }

    #[Test]
    public function constructorResolvesDynamicMetaFlags(): void
    {
        $multi = new MultiField('Address', [
            'dynamic' => true,
            'dynamicLabel' => 'Add another address',
        ]);

        $this->assertTrue($multi->isDynamic());
    }

    #[Test]
    public function constructorAcceptsOptionalNameParameter(): void
    {
        $multi = new MultiField('Full name', [], 'full-name');

        $this->assertSame('full-name', $multi->getName());
    }

    // --------------------------------------------------------------
    // addField / addText
    // --------------------------------------------------------------

    #[Test]
    public function addFieldCreatesChildElementAndReturnsIt(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $field = $multi->addField('first-name', ValidForm::VFORM_STRING);

        $this->assertInstanceOf(Text::class, $field);
        $this->assertTrue($multi->hasFields());
        $this->assertSame(1, $multi->getFields()->count());
    }

    #[Test]
    public function addFieldSetsMultiFieldAsParent(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $field = $multi->addField('first-name', ValidForm::VFORM_STRING);

        $this->assertSame($multi, $field->getMeta('parent'));
    }

    #[Test]
    public function addFieldStripsNestedDynamicMetaFromChildren(): void
    {
        // Creating dynamic fields inside a multifield is not supported — the
        // addField method must strip dynamic/dynamicLabel/dynamicRemoveLabel
        // from the child's meta to prevent nested dynamic issues.
        $multi = $this->form->addMultiField('Address');
        $field = $multi->addField(
            'street',
            ValidForm::VFORM_STRING,
            [],
            [],
            [
                'dynamic' => true,
                'dynamicLabel' => 'should be stripped',
                'dynamicRemoveLabel' => 'should be stripped',
                'class' => 'should-survive',
            ]
        );

        // The dynamic flags should have been stripped; other meta should survive.
        $this->assertFalse($field->isDynamic());
        $this->assertSame('should-survive', $field->getMeta('class'));
    }

    #[Test]
    public function addFieldInjectsHiddenCounterWhenMultiFieldIsDynamic(): void
    {
        $multi = new MultiField('Phone', ['dynamic' => true, 'dynamicLabel' => 'Add']);
        $multi->addField('phone', ValidForm::VFORM_STRING);

        // Two children: the Text field + the hidden dynamic counter.
        $this->assertSame(2, $multi->getFields()->count());
    }

    #[Test]
    public function addFieldDoesNotInjectHiddenCounterWhenMultiFieldIsNotDynamic(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);

        // Only the Text field, no hidden counter.
        $this->assertSame(1, $multi->getFields()->count());
    }

    #[Test]
    public function addTextCreatesStaticTextChild(): void
    {
        $multi = $this->form->addMultiField('Address');
        $text = $multi->addText('or');

        $this->assertInstanceOf(StaticText::class, $text);
        $this->assertSame(1, $multi->getFields()->count());
    }

    // --------------------------------------------------------------
    // Placeholders: getValue, getType, getId
    // --------------------------------------------------------------

    #[Test]
    public function getValueAlwaysReturnsTrue(): void
    {
        $multi = $this->form->addMultiField('Full name');

        // getValue() is a placeholder — MultiField is a container, not a value-bearing element.
        $this->assertTrue($multi->getValue());
    }

    #[Test]
    public function getTypeAlwaysReturnsZero(): void
    {
        $multi = $this->form->addMultiField('Full name');

        $this->assertSame(0, $multi->getType());
    }

    #[Test]
    public function getIdDelegatesToGetName(): void
    {
        $multi = new MultiField('Full name', [], 'full-name');

        $this->assertSame($multi->getName(), $multi->getId());
    }

    // --------------------------------------------------------------
    // hasFields / isDynamic
    // --------------------------------------------------------------

    #[Test]
    public function hasFieldsReturnsFalseWhenEmpty(): void
    {
        $multi = new MultiField('Empty');

        $this->assertFalse($multi->hasFields());
    }

    #[Test]
    public function hasFieldsReturnsTrueWhenPopulated(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);

        $this->assertTrue($multi->hasFields());
    }

    #[Test]
    public function isDynamicReturnsFalseByDefault(): void
    {
        $multi = $this->form->addMultiField('Full name');

        $this->assertFalse($multi->isDynamic());
    }

    // --------------------------------------------------------------
    // isValid
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueWhenAllChildrenPassValidation(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $this->assertTrue($multi->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenAChildFailsValidation(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField(
            'required-field',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        // 'required-field' has no submitted value.
        $this->assertFalse($multi->isValid());
    }

    // --------------------------------------------------------------
    // hasContent
    // --------------------------------------------------------------

    #[Test]
    public function hasContentReturnsFalseWhenNoChildHasValue(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);

        $this->assertFalse($multi->hasContent());
    }

    #[Test]
    public function hasContentReturnsTrueWhenAnyChildHasValue(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $_REQUEST['first-name'] = 'Robin';

        $this->assertTrue($multi->hasContent());
    }

    // --------------------------------------------------------------
    // setData / getData
    // --------------------------------------------------------------

    #[Test]
    public function setDataAndGetDataRoundTrip(): void
    {
        $multi = $this->form->addMultiField('Full name');

        $multi->setData('custom-key', 'custom-value');

        $this->assertSame('custom-value', $multi->getData('custom-key'));
    }

    #[Test]
    public function getDataReturnsFullArrayWhenNoKeyProvided(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->setData('a', 1);
        $multi->setData('b', 2);

        $this->assertSame(['a' => 1, 'b' => 2], $multi->getData());
    }

    #[Test]
    public function getDataReturnsFalseForMissingKey(): void
    {
        $multi = $this->form->addMultiField('Full name');

        $this->assertFalse($multi->getData('nonexistent'));
    }

    // --------------------------------------------------------------
    // toHtml
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlReturnsEmptyStringForMultiFieldWithNoChildren(): void
    {
        $multi = new MultiField('Empty');

        $this->assertSame('', $multi->toHtml());
    }

    #[Test]
    public function toHtmlRendersWrapperDivWithMultifieldClassAndLabel(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($multi->toHtml());

        // `//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]`
        // — the outer wrapper div whose class list includes the `vf__multifield` token.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]')->item(0);
        $this->assertNotNull($wrapper);

        // `//div/label` — the shared label is a direct child of the outer wrapper.
        $label = $xpath->query('//div/label')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Full name', trim($label->textContent));
    }

    #[Test]
    public function toHtmlRendersChildFieldsInSimpleLayoutMode(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($multi->toHtml());

        // `//div//input[@type="text"]` — text inputs rendered as descendants of the
        // outer wrapper. Each child renders in simple-layout mode (inside a
        // vf__multifielditem wrapper, no individual labels).
        $inputs = $xpath->query('//div//input[@type="text"]');
        $this->assertSame(2, $inputs->length);
        $this->assertSame('first-name', $inputs->item(0)->getAttribute('name'));
        $this->assertSame('last-name', $inputs->item(1)->getAttribute('name'));
    }

    #[Test]
    public function toHtmlRendersRequiredClassWhenAnyChildIsRequired(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING, ['required' => true]);

        $xpath = $this->parseHtml($multi->toHtml());

        // `//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]`
        // — the outer wrapper; class list should contain `vf__required` because at
        // least one child field is required.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsAddElementCallPerChildField(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $js = $multi->toJS();

        // Each child Text field emits one objForm.addElement() call.
        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'first-name'", $js);
        $this->assertStringContainsString("'last-name'", $js);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function addFieldPreventsNestedDynamicFieldCreation(): void
    {
        // SECURITY: nested dynamic fields could create unbounded DOM generation
        // or confuse the client-side duplication logic. addField() must strip
        // the dynamic/dynamicLabel/dynamicRemoveLabel meta keys from children.
        $multi = $this->form->addMultiField('Address');

        $field = $multi->addField(
            'street',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'attack']
        );

        // The child must NOT be dynamic.
        $this->assertFalse($field->isDynamic());
    }
}
