<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Fieldset;
use ValidFormBuilder\Hidden;
use ValidFormBuilder\MultiField;
use ValidFormBuilder\Note;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Fieldset}.
 *
 * Surface covered:
 * - Constructor: header, optional Note creation rules, meta storage
 * - addField(): Base type guard, parent meta wiring, dynamic counter injection,
 *   exclusions for MultiField/Area
 * - hasFields() / isDynamic() invariants
 * - getFields(): returns the internal Collection
 * - isValid(): empty fieldset, all-valid children, one invalid child
 * - toHtml(): wrapping <fieldset>, optional <legend>, embedded Note, child output
 * - toJS(): concatenates child JS
 * - getHeader / setHeader via ClassDynamic magic accessors
 */
class FieldsetTest extends TestCase
{
    use HtmlAssertionsTrait;

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorAcceptsNoArguments(): void
    {
        $fieldset = new Fieldset();

        $this->assertNull($fieldset->getHeader());
        $this->assertInstanceOf(Collection::class, $fieldset->getFields());
        $this->assertSame(0, $fieldset->getFields()->count());
    }

    #[Test]
    public function constructorStoresHeader(): void
    {
        $fieldset = new Fieldset('Section Title');

        $this->assertSame('Section Title', $fieldset->getHeader());
    }

    #[Test]
    public function constructorCreatesNoteWhenNoteHeaderProvided(): void
    {
        $fieldset = new Fieldset('Title', 'Heads up', null);

        // The internal __note property should be a Note instance.
        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertInstanceOf(Note::class, $ref->getValue($fieldset));
    }

    #[Test]
    public function constructorCreatesNoteWhenNoteBodyProvided(): void
    {
        $fieldset = new Fieldset('Title', null, 'Note body text');

        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertInstanceOf(Note::class, $ref->getValue($fieldset));
    }

    #[Test]
    public function constructorDoesNotCreateNoteWhenBothNoteArgsEmpty(): void
    {
        $fieldset = new Fieldset('Title', null, null);

        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertNull($ref->getValue($fieldset));
    }

    #[Test]
    public function constructorStoresMetaArray(): void
    {
        $fieldset = new Fieldset('Title', null, null, ['class' => 'my-fieldset']);

        $this->assertSame('my-fieldset', $fieldset->getMeta('class'));
    }

    // --------------------------------------------------------------
    // addField()
    // --------------------------------------------------------------

    #[Test]
    public function addFieldAppendsToFieldsCollection(): void
    {
        $fieldset = new Fieldset();
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $fieldset->addField($field);

        $this->assertSame(1, $fieldset->getFields()->count());
        $this->assertSame($field, $fieldset->getFields()->getFirst());
    }

    #[Test]
    public function addFieldSetsFieldsetAsParent(): void
    {
        $fieldset = new Fieldset();
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $fieldset->addField($field);

        $this->assertSame($fieldset, $field->getMeta('parent'));
    }

    #[Test]
    public function addFieldThrowsForNonBaseObject(): void
    {
        $fieldset = new Fieldset();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/should be an instance of/');

        $fieldset->addField(new \stdClass());
    }

    #[Test]
    public function addFieldInjectsHiddenCounterForDynamicField(): void
    {
        $fieldset = new Fieldset();
        $field = new Text(
            'phone',
            ValidForm::VFORM_STRING,
            'Phone',
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another phone']
        );

        $this->assertTrue($field->isDynamic());

        $fieldset->addField($field);

        // The fieldset should now contain the field plus a Hidden counter.
        $this->assertSame(2, $fieldset->getFields()->count());

        $items = [];
        foreach ($fieldset->getFields() as $item) {
            $items[] = $item;
        }

        $this->assertInstanceOf(Hidden::class, $items[1]);
        $this->assertSame($field->getId() . '_dynamic', $items[1]->getName());
    }

    #[Test]
    public function addFieldDoesNotInjectCounterForMultiField(): void
    {
        $fieldset = new Fieldset();
        $multi = new MultiField(
            'addresses',
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );

        // MultiField is intentionally excluded from the dynamic counter logic.
        $fieldset->addField($multi);

        $this->assertSame(1, $fieldset->getFields()->count());
    }

    #[Test]
    public function addFieldDoesNotInjectCounterForArea(): void
    {
        $fieldset = new Fieldset();
        $area = new Area(
            'address',
            true,
            'Address',
            true,
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );

        // Area is intentionally excluded from the dynamic counter logic.
        $fieldset->addField($area);

        $this->assertSame(1, $fieldset->getFields()->count());
    }

    #[Test]
    public function addFieldDoesNotInjectCounterForNonDynamicField(): void
    {
        $fieldset = new Fieldset();
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $fieldset->addField($field);

        // No counter — just the field itself.
        $this->assertSame(1, $fieldset->getFields()->count());
    }

    // --------------------------------------------------------------
    // hasFields / isDynamic / getFields
    // --------------------------------------------------------------

    #[Test]
    public function hasFieldsReturnsTrue(): void
    {
        $fieldset = new Fieldset();

        $this->assertTrue($fieldset->hasFields());
    }

    #[Test]
    public function isDynamicReturnsFalseEvenWithDynamicChildren(): void
    {
        $fieldset = new Fieldset();
        $dynamicField = new Text(
            'phone',
            ValidForm::VFORM_STRING,
            'Phone',
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );
        $fieldset->addField($dynamicField);

        // Fieldset itself is never dynamic — it is a container.
        $this->assertFalse($fieldset->isDynamic());
    }

    #[Test]
    public function getFieldsReturnsTheInternalCollection(): void
    {
        $fieldset = new Fieldset();

        $this->assertInstanceOf(Collection::class, $fieldset->getFields());
    }

    // --------------------------------------------------------------
    // isValid / __validate
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueForEmptyFieldset(): void
    {
        $fieldset = new Fieldset();

        $this->assertTrue($fieldset->isValid());
    }

    #[Test]
    public function isValidReturnsTrueWhenAllChildrenValid(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('a', ValidForm::VFORM_STRING, 'A'));
        $fieldset->addField(new Text('b', ValidForm::VFORM_STRING, 'B'));

        $this->assertTrue($fieldset->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenAChildIsInvalid(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('a', ValidForm::VFORM_STRING, 'A'));
        $fieldset->addField(new Text(
            'required-field',
            ValidForm::VFORM_STRING,
            'Required',
            ['required' => true]
        ));

        // 'required-field' has no submitted value, so it must be invalid.
        $this->assertFalse($fieldset->isValid());
    }

    // --------------------------------------------------------------
    // toHtml — structural assertions via DOMXPath
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersExactlyOneFieldsetElement(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(1, $xpath->query('//fieldset')->length);
    }

    #[Test]
    public function toHtmlFieldsetCarriesGeneratedIdAttribute(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());
        $node = $xpath->query('//fieldset')->item(0);

        $this->assertNotNull($node);
        $this->assertNotEmpty($node->getAttribute('id'));
        $this->assertStringStartsWith('fieldset_', $node->getAttribute('id'));
    }

    #[Test]
    public function toHtmlRendersLegendWithHeaderAsChildOfFieldset(): void
    {
        $fieldset = new Fieldset('Personal Information');

        $xpath = $this->parseHtml($fieldset->toHtml());
        $legends = $xpath->query('//fieldset/legend');

        $this->assertSame(1, $legends->length, 'Expected exactly one <legend> directly under <fieldset>');

        // Header text is wrapped in a <span> inside the legend.
        $span = $xpath->query('//fieldset/legend/span')->item(0);
        $this->assertNotNull($span);
        $this->assertSame('Personal Information', $span->textContent);
    }

    #[Test]
    public function toHtmlDoesNotRenderLegendWhenHeaderIsMissing(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(0, $xpath->query('//fieldset/legend')->length);
    }

    #[Test]
    public function toHtmlEmbedsNoteAsChildOfFieldsetWithCorrectStructure(): void
    {
        $fieldset = new Fieldset('Title', 'Note Header', 'Note body');

        $xpath = $this->parseHtml($fieldset->toHtml());

        // The Note renders as <div class="vf__notes"> with <h4>header</h4> and <p>body</p>.
        $note = $xpath->query('//fieldset/div[contains(concat(" ", normalize-space(@class), " "), " vf__notes ")]')->item(0);
        $this->assertNotNull($note, 'Expected vf__notes div directly under <fieldset>');

        $h4 = $xpath->query('./h4', $note)->item(0);
        $this->assertNotNull($h4);
        $this->assertSame('Note Header', $h4->textContent);

        $p = $xpath->query('./p', $note)->item(0);
        $this->assertNotNull($p);
        $this->assertSame('Note body', $p->textContent);
    }

    #[Test]
    public function toHtmlDoesNotEmbedNoteWhenNoteArgsAreEmpty(): void
    {
        $fieldset = new Fieldset('Title');

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(
            0,
            $xpath->query('//fieldset/div[contains(concat(" ", normalize-space(@class), " "), " vf__notes ")]')->length
        );
    }

    #[Test]
    public function toHtmlRendersEachChildFieldAsInputDescendantOfFieldset(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('marker-one', ValidForm::VFORM_STRING, 'One'));
        $fieldset->addField(new Text('marker-two', ValidForm::VFORM_STRING, 'Two'));

        $xpath = $this->parseHtml($fieldset->toHtml());
        $inputs = $xpath->query('//fieldset//input[@type="text"]');

        $this->assertSame(2, $inputs->length, 'Expected exactly two text inputs inside the fieldset');

        // Verify name + id attributes match the field definitions, not just stray substrings.
        $this->assertSame('marker-one', $inputs->item(0)->getAttribute('name'));
        $this->assertSame('marker-one', $inputs->item(0)->getAttribute('id'));
        $this->assertSame('marker-two', $inputs->item(1)->getAttribute('name'));
        $this->assertSame('marker-two', $inputs->item(1)->getAttribute('id'));
    }

    #[Test]
    public function toHtmlPreservesChildOrder(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('first', ValidForm::VFORM_STRING, 'First'));
        $fieldset->addField(new Text('second', ValidForm::VFORM_STRING, 'Second'));
        $fieldset->addField(new Text('third', ValidForm::VFORM_STRING, 'Third'));

        $xpath = $this->parseHtml($fieldset->toHtml());
        $inputs = $xpath->query('//fieldset//input[@type="text"]');

        $this->assertSame(3, $inputs->length);
        $this->assertSame(['first', 'second', 'third'], [
            $inputs->item(0)->getAttribute('name'),
            $inputs->item(1)->getAttribute('name'),
            $inputs->item(2)->getAttribute('name'),
        ]);
    }

    #[Test]
    public function toHtmlRendersChildLabelsLinkedToInputs(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('email', ValidForm::VFORM_EMAIL, 'Email Address'));

        $xpath = $this->parseHtml($fieldset->toHtml());
        $label = $xpath->query('//fieldset//label[@for="email"]')->item(0);

        $this->assertNotNull($label);
        $this->assertSame('Email Address', $label->textContent);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsAddElementCallForEachChild(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('alpha', ValidForm::VFORM_STRING, 'Alpha'));
        $fieldset->addField(new Text('beta', ValidForm::VFORM_STRING, 'Beta'));

        $js = $fieldset->toJS();

        // Each Text child emits exactly one objForm.addElement('<name>', ...) call.
        $this->assertSame(1, substr_count($js, "objForm.addElement('alpha'"));
        $this->assertSame(1, substr_count($js, "objForm.addElement('beta'"));
    }

    #[Test]
    public function toJsPreservesChildOrder(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('alpha', ValidForm::VFORM_STRING, 'Alpha'));
        $fieldset->addField(new Text('beta', ValidForm::VFORM_STRING, 'Beta'));

        $js = $fieldset->toJS();

        $this->assertLessThan(
            strpos($js, "objForm.addElement('beta'"),
            strpos($js, "objForm.addElement('alpha'"),
            'Expected alpha to be emitted before beta'
        );
    }

    #[Test]
    public function toJsForEmptyFieldsetReturnsEmptyString(): void
    {
        $fieldset = new Fieldset();

        $this->assertSame('', $fieldset->toJS());
    }

    // --------------------------------------------------------------
    // getHeader / setHeader (magic via ClassDynamic)
    // --------------------------------------------------------------

    #[Test]
    public function headerRoundTripsViaMagicAccessors(): void
    {
        $fieldset = new Fieldset('Original');

        $fieldset->setHeader('Updated');

        $this->assertSame('Updated', $fieldset->getHeader());
    }
}
