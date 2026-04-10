<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Element;
use ValidFormBuilder\FieldValidator;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Element}.
 *
 * Element is the shared base for the concrete field classes (Text,
 * Textarea, Hidden, etc.). Most tests exercise behaviour through a Text
 * instance because every Element-level method tested here is inherited
 * from Element unchanged; a couple of tests instantiate Element directly
 * to hit its placeholder toHtml() / toJS() implementations.
 *
 * Surface covered:
 * - Constructor wiring (id/name/label/type, meta resolution, validator creation)
 * - setClass() CSS class mapping for every VFORM_* type
 * - Magic getters/setters (getLabel, getTip, getType, getHint, getValidator, …)
 * - getRandomId() for simple names and name[] arrays
 * - isDynamicCounter default, hasFields default, isDynamic default
 * - getDefault / setDefault round trip
 * - setName updates both the field name and validator field name
 * - setError delegates to the validator
 * - isValid for non-dynamic fields (required + optional)
 * - getValue / __getValue branches (default, hint, submitted)
 * - getDynamicCount default
 * - toHtml() / toJS() placeholders on a bare Element
 */
class ElementTest extends TestCase
{
    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        foreach (['text-field', 'required-field', 'default-field', 'hint-field'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresNameLabelAndType(): void
    {
        $field = $this->form->addField(
            'text-field',
            'Text Field',
            ValidForm::VFORM_STRING
        );

        $this->assertSame('text-field', $field->getName());
        $this->assertSame('Text Field', $field->getLabel());
        $this->assertSame(ValidForm::VFORM_STRING, $field->getType());
    }

    #[Test]
    public function constructorUsesNameAsIdWhenNoBracketSuffix(): void
    {
        $field = $this->form->addField('plain-name', 'Label', ValidForm::VFORM_STRING);

        $this->assertSame('plain-name', $field->getId());
    }

    #[Test]
    public function constructorGeneratesRandomIdForArrayStyleName(): void
    {
        $field = $this->form->addField('checks[]', 'Checks', ValidForm::VFORM_STRING);

        $this->assertNotSame('checks[]', $field->getId());
        $this->assertStringStartsWith('checks_', $field->getId());
        $this->assertDoesNotMatchRegularExpression('/\[\]/', $field->getId());
    }

    #[Test]
    public function constructorResolvesMetaKeysOntoFieldProperties(): void
    {
        $field = $this->form->addField(
            'meta-field',
            'Meta Field',
            ValidForm::VFORM_STRING,
            [],
            [],
            [
                'tip' => 'Hello tip',
                'hint' => 'Hello hint',
                'default' => 'Hello default',
            ]
        );

        $this->assertSame('Hello tip', $field->getTip());
        $this->assertSame('Hello hint', $field->getHint());
        $this->assertSame('Hello default', $field->getDefault());
    }

    #[Test]
    public function constructorCreatesFieldValidatorInstance(): void
    {
        $field = $this->form->addField('validator-field', 'Label', ValidForm::VFORM_STRING);

        $this->assertInstanceOf(FieldValidator::class, $field->getValidator());
    }

    // --------------------------------------------------------------
    // setClass() — CSS class mapping
    // --------------------------------------------------------------

    #[DataProvider('cssClassTypeProvider')]
    #[Test]
    public function setClassAppliesTypeSpecificCssClasses(int $type, array $expectedClassTokens): void
    {
        $field = $this->form->addField('css-field', 'Label', $type);

        $class = $field->getFieldMeta('class', '');
        $tokens = preg_split('/\s+/', (string) $class, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($expectedClassTokens as $expected) {
            $this->assertContains($expected, $tokens, "Expected CSS class '$expected' for type $type");
        }
    }

    public static function cssClassTypeProvider(): array
    {
        return [
            'string'    => [ValidForm::VFORM_STRING,   ['vf__string', 'vf__text']],
            'word'      => [ValidForm::VFORM_WORD,     ['vf__word', 'vf__text']],
            'email'     => [ValidForm::VFORM_EMAIL,    ['vf__email', 'vf__text']],
            'url'       => [ValidForm::VFORM_URL,      ['vf__url', 'vf__text']],
            'currency'  => [ValidForm::VFORM_CURRENCY, ['vf__currency', 'vf__text']],
            'date'      => [ValidForm::VFORM_DATE,     ['vf__date', 'vf__text']],
            'numeric'   => [ValidForm::VFORM_NUMERIC,  ['vf__numeric', 'vf__text']],
            'integer'   => [ValidForm::VFORM_INTEGER,  ['vf__integer', 'vf__text']],
            'password'  => [ValidForm::VFORM_PASSWORD, ['vf__password', 'vf__text']],
        ];
    }

    #[Test]
    public function selectListWithoutMultipleUsesOneClass(): void
    {
        $field = $this->form->addField('single-select', 'Single Select', ValidForm::VFORM_SELECT_LIST);

        $tokens = preg_split('/\s+/', (string) $field->getFieldMeta('class', ''), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__one', $tokens);
        $this->assertContains('vf__select', $tokens);
    }

    #[Test]
    public function selectListWithMultipleUsesMultipleClass(): void
    {
        $field = $this->form->addField(
            'multi-select',
            'Multi Select',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['multiple' => 'multiple']
        );

        $tokens = preg_split('/\s+/', (string) $field->getFieldMeta('class', ''), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__multiple', $tokens);
        $this->assertContains('vf__select', $tokens);
    }

    // --------------------------------------------------------------
    // Magic getters / setters (from ClassDynamic)
    // --------------------------------------------------------------

    #[Test]
    public function magicSettersAndGettersForLabelTipHintType(): void
    {
        $field = $this->form->addField('magic-field', 'Original', ValidForm::VFORM_STRING);

        $field->setLabel('Updated');
        $field->setTip('New tip');
        $field->setHint('New hint');
        $field->setType(ValidForm::VFORM_EMAIL);

        $this->assertSame('Updated', $field->getLabel());
        $this->assertSame('New tip', $field->getTip());
        $this->assertSame('New hint', $field->getHint());
        $this->assertSame(ValidForm::VFORM_EMAIL, $field->getType());
    }

    // --------------------------------------------------------------
    // getRandomId
    // --------------------------------------------------------------

    #[Test]
    public function getRandomIdForSimpleName(): void
    {
        $field = $this->form->addField('anchor', 'Anchor', ValidForm::VFORM_STRING);

        $id = $field->getRandomId('some-name');

        $this->assertMatchesRegularExpression('/^some-name_\d{6}$/', $id);
    }

    #[Test]
    public function getRandomIdForBracketName(): void
    {
        $field = $this->form->addField('anchor', 'Anchor', ValidForm::VFORM_STRING);

        $id = $field->getRandomId('items[]');

        // Bracket syntax must be stripped and replaced with a random suffix.
        $this->assertMatchesRegularExpression('/^items_\d{6}$/', $id);
    }

    #[Test]
    public function getRandomIdProducesDifferentValuesAcrossCalls(): void
    {
        $field = $this->form->addField('anchor', 'Anchor', ValidForm::VFORM_STRING);

        // Collect several ids — they should not all collapse to the same value.
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $field->getRandomId('x');
        }

        $this->assertGreaterThan(1, count(array_unique($ids)));
    }

    // --------------------------------------------------------------
    // Default flags: isDynamicCounter / hasFields / isDynamic
    // --------------------------------------------------------------

    #[Test]
    public function isDynamicCounterDefaultsToFalse(): void
    {
        $field = $this->form->addField('field', 'Label', ValidForm::VFORM_STRING);

        $this->assertFalse($field->isDynamicCounter());
    }

    #[Test]
    public function hasFieldsDefaultsToFalseForPlainElement(): void
    {
        $field = $this->form->addField('field', 'Label', ValidForm::VFORM_STRING);

        $this->assertFalse($field->hasFields());
    }

    #[Test]
    public function isDynamicDefaultsToFalse(): void
    {
        $field = $this->form->addField('field', 'Label', ValidForm::VFORM_STRING);

        $this->assertFalse($field->isDynamic());
    }

    // --------------------------------------------------------------
    // getDefault / setDefault
    // --------------------------------------------------------------

    #[Test]
    public function setAndGetDefaultRoundTrip(): void
    {
        $field = $this->form->addField('default-field', 'Label', ValidForm::VFORM_STRING);

        $field->setDefault('round-trip value');

        $this->assertSame('round-trip value', $field->getDefault());
    }

    #[Test]
    public function setAndGetDefaultAcceptsArray(): void
    {
        $field = $this->form->addField('default-field', 'Label', ValidForm::VFORM_STRING);

        $field->setDefault(['a', 'b', 'c']);

        $this->assertSame(['a', 'b', 'c'], $field->getDefault());
    }

    // --------------------------------------------------------------
    // setName propagates to validator
    // --------------------------------------------------------------

    #[Test]
    public function setNameUpdatesFieldAndValidatorFieldName(): void
    {
        $field = $this->form->addField('old-name', 'Label', ValidForm::VFORM_STRING);

        $field->setName('new-name');

        $this->assertSame('new-name', $field->getName());

        // The validator must also be updated so it reads from $_REQUEST['new-name'].
        $ref = new \ReflectionProperty(FieldValidator::class, '__fieldname');
        $ref->setAccessible(true);
        $this->assertSame('new-name', $ref->getValue($field->getValidator()));
    }

    // --------------------------------------------------------------
    // setError delegates to validator
    // --------------------------------------------------------------

    #[Test]
    public function setErrorDelegatesToValidator(): void
    {
        $field = $this->form->addField('error-field', 'Label', ValidForm::VFORM_STRING);

        $field->setError('Custom error message');

        $ref = new \ReflectionProperty(FieldValidator::class, '__overrideerrors');
        $ref->setAccessible(true);
        $overrides = $ref->getValue($field->getValidator());

        $this->assertSame('Custom error message', $overrides[0]);
    }

    #[Test]
    public function setErrorAtDynamicPosition(): void
    {
        $field = $this->form->addField('error-field', 'Label', ValidForm::VFORM_STRING);

        $field->setError('Dynamic error', 2);

        $ref = new \ReflectionProperty(FieldValidator::class, '__overrideerrors');
        $ref->setAccessible(true);
        $overrides = $ref->getValue($field->getValidator());

        $this->assertSame('Dynamic error', $overrides[2]);
    }

    // --------------------------------------------------------------
    // isValid
    // --------------------------------------------------------------

    #[Test]
    public function isValidForOptionalFieldWithoutInputReturnsTrue(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);

        $this->assertTrue($field->isValid());
    }

    #[Test]
    public function isValidForRequiredFieldWithoutInputReturnsFalse(): void
    {
        $field = $this->form->addField(
            'required-field',
            'Required',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        $this->assertFalse($field->isValid());
    }

    #[Test]
    public function isValidForRequiredFieldWithInputReturnsTrue(): void
    {
        $field = $this->form->addField(
            'required-field',
            'Required',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );
        $_REQUEST['required-field'] = 'hello';

        $this->assertTrue($field->isValid());
    }

    // --------------------------------------------------------------
    // getValue / __getValue
    // --------------------------------------------------------------

    #[Test]
    public function getValueReturnsNullBeforeValidation(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);
        $_REQUEST['text-field'] = 'hello';

        // getValue() at position 0 reads from the validator cache without
        // running validate() — callers must validate first for a meaningful value.
        $this->assertNull($field->getValue());
    }

    #[Test]
    public function getValueReturnsValidValueAfterValidation(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);
        $_REQUEST['text-field'] = 'hello';

        $field->isValid();

        $this->assertSame('hello', $field->getValue());
    }

    #[Test]
    public function privateGetValueNotSubmittedReturnsDefault(): void
    {
        $field = $this->form->addField(
            'default-field',
            'Label',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['default' => 'default value']
        );

        $this->assertSame('default value', $field->__getValue(false));
    }

    #[Test]
    public function privateGetValueNotSubmittedFallsBackToHintWhenNoDefault(): void
    {
        $field = $this->form->addField(
            'hint-field',
            'Label',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['hint' => 'fallback hint']
        );

        $this->assertSame('fallback hint', $field->__getValue(false));
    }

    #[Test]
    public function privateGetValueReturnsNullWithNoDefaultNoHintNoSubmission(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);

        $this->assertNull($field->__getValue(false));
    }

    #[Test]
    public function privateGetValueSubmittedReadsFromRequest(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);
        $_REQUEST['text-field'] = 'submitted value';

        $this->assertSame('submitted value', $field->__getValue(true));
    }

    // --------------------------------------------------------------
    // getDynamicCount
    // --------------------------------------------------------------

    #[Test]
    public function getDynamicCountForNonDynamicFieldIsZero(): void
    {
        $field = $this->form->addField('text-field', 'Label', ValidForm::VFORM_STRING);

        $this->assertSame(0, $field->getDynamicCount());
    }

    // --------------------------------------------------------------
    // Placeholder toHtml / toJS (via bare Element)
    // --------------------------------------------------------------

    #[Test]
    public function bareElementToHtmlReturnsPlaceholder(): void
    {
        // Use an unknown type so Base / ValidForm::renderField() would not map
        // to a concrete subclass. We build Element directly for this test.
        $element = new Element('bare', ValidForm::VFORM_STRING, 'Label');

        $this->assertSame('Field type not defined.', $element->toHtml());
    }

    #[Test]
    public function bareElementToJsReturnsPlaceholder(): void
    {
        $element = new Element('bare', ValidForm::VFORM_STRING, 'Label');

        // Element::toJS() is a placeholder for subclasses; the exact text is part
        // of the contract because user-visible JS surfaces it.
        $this->assertSame(
            "alert('Field type of field bare not defined.');\n",
            $element->toJS()
        );
    }
}
