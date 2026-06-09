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
        foreach (['first-name', 'last-name', 'required-field', 'phone', 'phone_dynamic'] as $key) {
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

    #[Test]
    public function toHtmlRendersErrorParagraphsWhenSubmittedInvalid(): void
    {
        $multi = $this->form->addMultiField('Full name');
        $multi->addField(
            'first-name',
            ValidForm::VFORM_STRING,
            ['required' => true],
            ['required' => 'This field is required']
        );
        $multi->addField(
            'last-name',
            ValidForm::VFORM_STRING,
            ['required' => true],
            ['required' => 'This field is required']
        );

        // Submitted with no values: both required children fail validation.
        $xpath = $this->parseHtml($multi->toHtml(true));

        // The wrapper picks up the vf__error class.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);

        // `//p[@class="vf__error"]` — identical error messages are de-duplicated,
        // so two failing children with the same message render a single paragraph.
        $errors = $xpath->query('//p[@class="vf__error"]');
        $this->assertSame(1, $errors->length);
        $this->assertSame('This field is required', trim($errors->item(0)->textContent));
    }

    #[Test]
    public function toHtmlRendersRemoveLabelWhenRemovable(): void
    {
        $multi = new MultiField('Phone numbers', [
            'dynamic' => true,
            'dynamicLabel' => 'Add another phone',
            'dynamicRemoveLabel' => 'Remove this phone',
        ], 'phones');
        $multi->addField('phone', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($multi->toHtml());

        // The wrapper carries the vf__removable class.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__multifield ")]')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);

        // `//a[@class="vf__removeLabel"]` — the remove anchor inside the wrapper.
        $removeAnchor = $xpath->query('//a[@class="vf__removeLabel"]')->item(0);
        $this->assertNotNull($removeAnchor);
        $this->assertSame('Remove this phone', trim($removeAnchor->textContent));
    }

    #[Test]
    public function toHtmlRendersTipElementWhenTipPropertySet(): void
    {
        // NOTE: MultiField::__toHtml() reads $this->__tip, but neither MultiField
        // nor Base declares a $__tip property (only Element does). Because
        // ClassDynamic has no __isset(), `empty($this->__tip)` is always true for
        // a plain MultiField and setTip() throws BadMethodCallException — the tip
        // branch is unreachable through the public API. A subclass declaring the
        // property is the only way to exercise (and use) it.
        // The explicit name matters: a MultiField's id is its name, and the
        // generated fallback embeds the anonymous class name (which contains a
        // NUL byte and a file path) — libxml on some platforms refuses to parse
        // the malformed attribute.
        $multi = new class ('Full name', [], 'full-name-multifield') extends MultiField {
            protected $__tip = 'Enter both names';
        };
        $multi->addField('first-name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($multi->toHtml());

        // `//small[@class="vf__tip"]` — the tip element after the child fields.
        $tip = $xpath->query('//small[@class="vf__tip"]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Enter both names', trim($tip->textContent));
    }

    // --------------------------------------------------------------
    // toHtml — dynamic rendering (original + clones)
    // --------------------------------------------------------------

    #[Test]
    public function dynamicMultiFieldRendersOriginalAndCloneWrappers(): void
    {
        $multi = new MultiField('Phone numbers', [
            'dynamic' => true,
            'dynamicLabel' => 'Add another phone',
        ], 'phones');
        $multi->addField('phone', ValidForm::VFORM_STRING);

        // Simulate a submission where the user duplicated the multifield once.
        $_REQUEST['phone_dynamic'] = '1';

        $xpath = $this->parseHtml($multi->toHtml());

        // `//div[@id="phones"]` / `//div[@id="phones_1"]` — original and clone wrappers.
        $original = $xpath->query('//div[@id="phones"]')->item(0);
        $clone = $xpath->query('//div[@id="phones_1"]')->item(0);
        $this->assertNotNull($original);
        $this->assertNotNull($clone);

        $this->assertSame('original', $original->getAttribute('data-dynamic'));
        $this->assertSame('clone', $clone->getAttribute('data-dynamic'));

        $cloneClassTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $cloneClassTokens);

        // The clone renders the child field with the _1 suffix.
        $this->assertSame(1, $xpath->query('//input[@name="phone_1"]')->length);

        // The hidden dynamic counter renders exactly once (skipped in clones).
        $this->assertSame(1, $xpath->query('//input[@name="phone_dynamic"]')->length);

        // The duplication trigger lists the child field (counter excluded).
        // `//div[@class="vf__dynamic"]/a` — the 'add another' anchor.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('phone', $anchor->getAttribute('data-target-id'));
        $this->assertSame('phone', $anchor->getAttribute('data-target-name'));
        $this->assertSame('Add another phone', trim($anchor->textContent));
    }

    #[Test]
    public function getDynamicCountReadsSubmittedCounterValue(): void
    {
        $multi = new MultiField('Phone numbers', [
            'dynamic' => true,
            'dynamicLabel' => 'Add another phone',
        ], 'phones');
        $multi->addField('phone', ValidForm::VFORM_STRING);

        $_REQUEST['phone_dynamic'] = '2';

        // NOTE: like Area (and unlike Element), MultiField::getDynamicCount()
        // does not cast — the raw request string leaks through.
        $this->assertSame('2', $multi->getDynamicCount());
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

    #[Test]
    public function toJsEmitsAddElementPerDynamicPositionForDynamicMultiField(): void
    {
        $multi = new MultiField('Phone numbers', [
            'dynamic' => true,
            'dynamicLabel' => 'Add another phone',
        ], 'phones');
        $multi->addField('phone', ValidForm::VFORM_STRING);

        $_REQUEST['phone_dynamic'] = '1';

        $js = $multi->toJS();

        // The dynamic child registers itself once per dynamic position.
        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'phone'", $js);
        $this->assertStringContainsString("'phone_1'", $js);
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
