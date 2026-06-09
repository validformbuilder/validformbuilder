<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Group;
use ValidFormBuilder\GroupField;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Group}.
 *
 * Group wraps a collection of radio buttons or checkboxes (GroupField
 * instances). It overrides Element::getName() and ::getId() for the
 * checkbox-list `[]` suffix convention and adds a custom addField()
 * that creates GroupField children with random ids.
 *
 * Surface covered:
 * - Constructor: initialises empty __fields collection, delegates to parent.
 * - getId() / getName() bracket-stripping logic for both radio and check lists.
 * - addField(): creates GroupField children, sets parent meta, accumulates
 *   default values differently for radio (last checked) vs check (array).
 * - hasFields() inconsistency (Element returns false, but getFields() works).
 * - Rendering: outer <div>, inner <fieldset>, per-option label+input,
 *   required/optional class tokens, checked attribute on defaults, tip.
 * - toJS: objForm.addElement() call with correct id/name.
 *
 * Security audit:
 * - Option values rendered via GroupField must be HTML-escaped in attributes.
 * - getName() cannot be manipulated to inject brackets or extra characters.
 */
class GroupTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        foreach (['color', 'fruit', 'fruit[]', 'xss-group', 'xss-group[]', 'color_dynamic'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor and accessors
    // --------------------------------------------------------------

    #[Test]
    public function radioListConstructionReturnsGroupInstance(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(ValidForm::VFORM_RADIO_LIST, $group->getType());
        $this->assertSame('Color', $group->getLabel());
    }

    #[Test]
    public function checkListConstructionReturnsGroupInstance(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame(ValidForm::VFORM_CHECK_LIST, $group->getType());
    }

    #[Test]
    public function constructorInitialisesEmptyFieldsCollection(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);

        // Group::getFields() works via ClassDynamic magic: getFields() resolves
        // to $this->__fields, which is initialised as an empty Collection.
        $this->assertInstanceOf(Collection::class, $group->getFields());
        $this->assertSame(0, $group->getFields()->count());
    }

    // --------------------------------------------------------------
    // getId() / getName()
    // --------------------------------------------------------------

    #[Test]
    public function radioListNameIsPlainWithoutBrackets(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);

        $this->assertSame('color', $group->getName());
    }

    #[Test]
    public function checkListNameGetsBracketSuffix(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);

        // VFORM_CHECK_LIST automatically appends `[]` so PHP parses the POST as an array.
        $this->assertSame('fruit[]', $group->getName());
    }

    #[Test]
    public function getNameWithPlainFlagReturnsRawStoredName(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);

        // The `$blnPlain = true` parameter bypasses the bracket logic.
        $this->assertSame('fruit', $group->getName(true));
    }

    #[Test]
    public function getIdStripsBracketsFromChecklistId(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);

        // getId() strips `[]` to produce a valid HTML id attribute.
        $this->assertSame('fruit', $group->getId());
    }

    #[Test]
    public function getIdReturnsPlainIdForRadioList(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);

        $this->assertSame('color', $group->getId());
    }

    // --------------------------------------------------------------
    // addField() — creates GroupField children
    // --------------------------------------------------------------

    #[Test]
    public function addFieldCreatesGroupFieldChildAndReturnsIt(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $option = $group->addField('Red', 'red');

        $this->assertInstanceOf(GroupField::class, $option);
        $this->assertSame(1, $group->getFields()->count());
    }

    #[Test]
    public function addFieldSetsGroupAsParent(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $option = $group->addField('Red', 'red');

        $this->assertSame($group, $option->getMeta('parent'));
    }

    #[Test]
    public function addFieldAssignsRandomIdToEachGroupField(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $red = $group->addField('Red', 'red');
        $blue = $group->addField('Blue', 'blue');

        // Each option gets a unique random-suffix id based on the group name.
        $this->assertNotSame($red->getId(), $blue->getId());
        $this->assertStringStartsWith('color_', $red->getId());
        $this->assertStringStartsWith('color_', $blue->getId());
    }

    #[Test]
    public function radioListDefaultValueIsLastCheckedOption(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red', true);
        $group->addField('Blue', 'blue', true);

        // For radio, last checked wins (single-value).
        $this->assertSame('blue', $group->getDefault());
    }

    #[Test]
    public function checkListDefaultAccumulatesCheckedValues(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $group->addField('Apple', 'apple', true);
        $group->addField('Banana', 'banana');
        $group->addField('Cherry', 'cherry', true);

        // For check list, checked values accumulate into an array.
        $default = $group->getDefault();
        $this->assertIsArray($default);
        $this->assertContains('apple', $default);
        $this->assertContains('cherry', $default);
        $this->assertNotContains('banana', $default);
    }

    #[Test]
    public function uncheckedGroupHasNullDefault(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $this->assertNull($group->getDefault());
    }

    // --------------------------------------------------------------
    // hasFields inconsistency
    // --------------------------------------------------------------

    #[Test]
    public function hasFieldsReturnsFalseEvenThoughGroupFieldsExist(): void
    {
        // NOTE: Group inherits Element::hasFields() which always returns false.
        // This is inconsistent: getFields() returns a Collection with actual
        // GroupField children. Any code that branches on hasFields() — such as
        // Collection::removeRecursive() — would fail to descend into the Group.
        // Documented here as a known limitation rather than a fix, because
        // overriding hasFields() on Group could change behaviour for callers that
        // specifically treat Group as a leaf element.
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $this->assertFalse($group->hasFields());
        $this->assertSame(2, $group->getFields()->count());
    }

    // --------------------------------------------------------------
    // toHtml — structural rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlWrapsOptionsInFieldsetInsideDiv(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $xpath = $this->parseHtml($group->toHtml());

        // `//div/fieldset` — inner fieldset as a direct child of the wrapper div.
        $this->assertSame(1, $xpath->query('//div/fieldset')->length);
    }

    #[Test]
    public function toHtmlRendersOneRadioInputPerOption(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $xpath = $this->parseHtml($group->toHtml());

        // `//fieldset//input[@type="radio"]` — all radio inputs anywhere inside the fieldset.
        $radios = $xpath->query('//fieldset//input[@type="radio"]');
        $this->assertSame(2, $radios->length);
        $this->assertSame('red', $radios->item(0)->getAttribute('value'));
        $this->assertSame('blue', $radios->item(1)->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersOneCheckboxInputPerCheckListOption(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $group->addField('Apple', 'apple');
        $group->addField('Banana', 'banana');

        $xpath = $this->parseHtml($group->toHtml());

        // `//fieldset//input[@type="checkbox"]` — all checkbox inputs inside the fieldset.
        $checks = $xpath->query('//fieldset//input[@type="checkbox"]');
        $this->assertSame(2, $checks->length);
        $this->assertSame('apple', $checks->item(0)->getAttribute('value'));
        $this->assertSame('banana', $checks->item(1)->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersCheckedAttributeOnDefaultOption(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue', true);

        $xpath = $this->parseHtml($group->toHtml());

        // `//fieldset//input[@type="radio"]` — grab both radio inputs by document order.
        $radios = $xpath->query('//fieldset//input[@type="radio"]');
        $this->assertSame('', $radios->item(0)->getAttribute('checked'));
        $this->assertSame('checked', $radios->item(1)->getAttribute('checked'));
    }

    #[Test]
    public function toHtmlRendersGroupLabelOutsideFieldset(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');

        $xpath = $this->parseHtml($group->toHtml());

        // `//div/label` — the group label is a direct child of the wrapper div,
        // not inside the fieldset (which wraps only the options).
        $label = $xpath->query('//div/label')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Color', trim($label->textContent));
    }

    #[Test]
    public function toHtmlRendersRequiredClassWhenRequired(): void
    {
        $group = $this->form->addField(
            'color',
            'Color',
            ValidForm::VFORM_RADIO_LIST,
            ['required' => true]
        );
        $group->addField('Red', 'red');

        $xpath = $this->parseHtml($group->toHtml());
        // `//div` — the outer wrapper whose class reflects the required state.
        $wrapper = $xpath->query('//div')->item(0);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function radioInputsShareGroupNameAttribute(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $xpath = $this->parseHtml($group->toHtml());
        // `//fieldset//input[@type="radio"]` — both radios must share the same name
        // attribute (`color`) so the browser enforces mutual exclusion.
        $radios = $xpath->query('//fieldset//input[@type="radio"]');
        $this->assertSame('color', $radios->item(0)->getAttribute('name'));
        $this->assertSame('color', $radios->item(1)->getAttribute('name'));
    }

    #[Test]
    public function checkboxInputsShareBracketedNameAttribute(): void
    {
        $group = $this->form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $group->addField('Apple', 'apple');
        $group->addField('Banana', 'banana');

        $xpath = $this->parseHtml($group->toHtml());
        // `//fieldset//input[@type="checkbox"]` — both checkboxes must share the
        // name `fruit[]` so PHP parses the submission as an array.
        $checks = $xpath->query('//fieldset//input[@type="checkbox"]');
        $this->assertSame('fruit[]', $checks->item(0)->getAttribute('name'));
        $this->assertSame('fruit[]', $checks->item(1)->getAttribute('name'));
    }

    #[Test]
    public function toHtmlRendersErrorParagraphWhenSubmittedInvalid(): void
    {
        $group = $this->form->addField(
            'color',
            'Color',
            ValidForm::VFORM_RADIO_LIST,
            ['required' => true],
            ['required' => 'Please pick a color']
        );
        $group->addField('Red', 'red');

        // Submitted with no value: the required check fails.
        $xpath = $this->parseHtml($group->toHtml(true));

        // `//div` — the outer wrapper picks up the vf__error class.
        $wrapper = $xpath->query('//div')->item(0);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);

        // `//div/p[@class="vf__error"]` — the error message paragraph before the label.
        $error = $xpath->query('//div/p[@class="vf__error"]')->item(0);
        $this->assertNotNull($error);
        $this->assertSame('Please pick a color', trim($error->textContent));
    }

    #[Test]
    public function toHtmlRendersNoLabelClassWhenLabelDisabled(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');

        // toHtml($submitted, $blnSimpleLayout, $blnLabel) — disable the label.
        $xpath = $this->parseHtml($group->toHtml(false, false, false));

        // `//div` — the wrapper gets the vf__nolabel class instead of a label.
        $wrapper = $xpath->query('//div')->item(0);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__nolabel', $classTokens);

        // `//div/label` — no group label rendered.
        $this->assertSame(0, $xpath->query('//div/label')->length);
    }

    #[Test]
    public function toHtmlSimpleLayoutRendersMultiFieldItemWrapper(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');

        // Simple layout is used when the group lives inside a MultiField.
        $xpath = $this->parseHtml($group->toHtml(false, true));

        // `//div` — the wrapper uses vf__multifielditem instead of vf__optional.
        $wrapper = $xpath->query('//div')->item(0);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__multifielditem', $classTokens);
        $this->assertNotContains('vf__optional', $classTokens);

        // No group label in simple layout.
        $this->assertSame(0, $xpath->query('//div/label')->length);

        // The option still renders inside the fieldset.
        $this->assertSame(1, $xpath->query('//fieldset//input[@type="radio"]')->length);
    }

    #[Test]
    public function toHtmlSimpleLayoutRendersErrorClassWhenSubmittedInvalid(): void
    {
        $group = $this->form->addField(
            'color',
            'Color',
            ValidForm::VFORM_RADIO_LIST,
            ['required' => true],
            ['required' => 'Please pick a color']
        );
        $group->addField('Red', 'red');

        // Submitted + invalid in simple layout: error class only, no <p> message
        // (the parent MultiField renders the message).
        $xpath = $this->parseHtml($group->toHtml(true, true));

        $wrapper = $xpath->query('//div')->item(0);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);
        $this->assertContains('vf__multifielditem', $classTokens);

        $this->assertSame(0, $xpath->query('//p[@class="vf__error"]')->length);
    }

    #[Test]
    public function toHtmlRendersTipElement(): void
    {
        $group = $this->form->addField(
            'color',
            'Color',
            ValidForm::VFORM_RADIO_LIST,
            [],
            [],
            ['tip' => 'Choose wisely']
        );
        $group->addField('Red', 'red');

        $xpath = $this->parseHtml($group->toHtml());

        // `//small[@class="vf__tip"]` — the tip element after the fieldset.
        $tip = $xpath->query('//small[@class="vf__tip"]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Choose wisely', trim($tip->textContent));
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsExactlyOneAddElementCall(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');

        $js = $group->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
    }

    #[Test]
    public function toJsUsesGroupIdAndNameAsFirstTwoArguments(): void
    {
        $group = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Red', 'red');

        $js = $group->toJS();

        // The first two positional args should be the Group's stripped id and name.
        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('color',\\s*'color',/",
            $js
        );
    }

    #[Test]
    public function toJsEmitsSuffixedAddElementCallsForDynamicGroup(): void
    {
        $group = $this->form->addField(
            'color',
            'Color',
            ValidForm::VFORM_RADIO_LIST,
            ['required' => true],
            ['required' => 'Please pick a color'],
            ['dynamic' => true, 'dynamicLabel' => 'Add another color']
        );
        $group->addField('Red', 'red');

        // Simulate a submission where the user duplicated the group once.
        $_REQUEST['color_dynamic'] = '1';

        $js = $group->toJS();

        // One addElement call per dynamic position, with _1 suffixed id/name.
        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("objForm.addElement('color', 'color',", $js);
        $this->assertStringContainsString("objForm.addElement('color_1', 'color_1',", $js);

        // Dynamic clones are never required: with a dynamic count > 0, required
        // is forced to false for every position.
        $this->assertSame(0, substr_count($js, 'true'));
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function optionValuesAreEscapedInRenderedInputAttributes(): void
    {
        // SECURITY: an option value crafted to break out of the `value="…"`
        // attribute must be escaped before reaching the DOM. GroupField renders
        // via toHtmlInternal which should escape attribute values.
        $group = $this->form->addField('xss-group', 'XSS', ValidForm::VFORM_RADIO_LIST);
        $group->addField('Normal', 'safe');
        $group->addField('Attack', '"><img src=x onerror=alert(1)>');

        $xpath = $this->parseHtml($group->toHtml());

        // `//fieldset//input[@type="radio"]` — the two radio inputs.
        $radios = $xpath->query('//fieldset//input[@type="radio"]');
        $this->assertSame(2, $radios->length);

        // The attack payload survives as a literal attribute value, not as injected HTML.
        $this->assertSame('"><img src=x onerror=alert(1)>', $radios->item(1)->getAttribute('value'));

        // No injected <img> element escaped into the DOM.
        // `//img[@onerror]` — any <img> with an onerror attribute anywhere in the fragment.
        $this->assertSame(0, $xpath->query('//img[@onerror]')->length);
    }

    #[Test]
    public function getNameCannotBeManipulatedToInjectExtraBrackets(): void
    {
        // SECURITY: getName() for VFORM_CHECK_LIST appends `[]` only if not
        // already present. Verify that an attacker who tries to pass `fruit[]`
        // as the field name doesn't get `fruit[][]`.
        $group = $this->form->addField('fruit[]', 'Fruit', ValidForm::VFORM_CHECK_LIST);

        $this->assertSame('fruit[]', $group->getName());
        $this->assertStringNotContainsString('[][]', $group->getName());
    }
}
