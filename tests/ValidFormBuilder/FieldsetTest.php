<?php

namespace ValidFormBuilder;

use PHPUnit\Framework\TestCase;

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
    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    public function testConstructorAcceptsNoArguments(): void
    {
        $fieldset = new Fieldset();

        $this->assertNull($fieldset->getHeader());
        $this->assertInstanceOf(Collection::class, $fieldset->getFields());
        $this->assertSame(0, $fieldset->getFields()->count());
    }

    public function testConstructorStoresHeader(): void
    {
        $fieldset = new Fieldset('Section Title');

        $this->assertSame('Section Title', $fieldset->getHeader());
    }

    public function testConstructorCreatesNoteWhenNoteHeaderProvided(): void
    {
        $fieldset = new Fieldset('Title', 'Heads up', null);

        // The internal __note property should be a Note instance.
        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertInstanceOf(Note::class, $ref->getValue($fieldset));
    }

    public function testConstructorCreatesNoteWhenNoteBodyProvided(): void
    {
        $fieldset = new Fieldset('Title', null, 'Note body text');

        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertInstanceOf(Note::class, $ref->getValue($fieldset));
    }

    public function testConstructorDoesNotCreateNoteWhenBothNoteArgsEmpty(): void
    {
        $fieldset = new Fieldset('Title', null, null);

        $ref = new \ReflectionProperty(Fieldset::class, '__note');
        $ref->setAccessible(true);

        $this->assertNull($ref->getValue($fieldset));
    }

    public function testConstructorStoresMetaArray(): void
    {
        $fieldset = new Fieldset('Title', null, null, ['class' => 'my-fieldset']);

        $this->assertSame('my-fieldset', $fieldset->getMeta('class'));
    }

    // --------------------------------------------------------------
    // addField()
    // --------------------------------------------------------------

    public function testAddFieldAppendsToFieldsCollection(): void
    {
        $fieldset = new Fieldset();
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $fieldset->addField($field);

        $this->assertSame(1, $fieldset->getFields()->count());
        $this->assertSame($field, $fieldset->getFields()->getFirst());
    }

    public function testAddFieldSetsFieldsetAsParent(): void
    {
        $fieldset = new Fieldset();
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $fieldset->addField($field);

        $this->assertSame($fieldset, $field->getMeta('parent'));
    }

    public function testAddFieldThrowsForNonBaseObject(): void
    {
        $fieldset = new Fieldset();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/should be an instance of/');

        $fieldset->addField(new \stdClass());
    }

    public function testAddFieldInjectsHiddenCounterForDynamicField(): void
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

    public function testAddFieldDoesNotInjectCounterForMultiField(): void
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

    public function testAddFieldDoesNotInjectCounterForArea(): void
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

    public function testAddFieldDoesNotInjectCounterForNonDynamicField(): void
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

    public function testHasFieldsReturnsTrue(): void
    {
        $fieldset = new Fieldset();

        $this->assertTrue($fieldset->hasFields());
    }

    public function testIsDynamicReturnsFalseEvenWithDynamicChildren(): void
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

    public function testGetFieldsReturnsTheInternalCollection(): void
    {
        $fieldset = new Fieldset();

        $this->assertInstanceOf(Collection::class, $fieldset->getFields());
    }

    // --------------------------------------------------------------
    // isValid / __validate
    // --------------------------------------------------------------

    public function testIsValidReturnsTrueForEmptyFieldset(): void
    {
        $fieldset = new Fieldset();

        $this->assertTrue($fieldset->isValid());
    }

    public function testIsValidReturnsTrueWhenAllChildrenValid(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('a', ValidForm::VFORM_STRING, 'A'));
        $fieldset->addField(new Text('b', ValidForm::VFORM_STRING, 'B'));

        $this->assertTrue($fieldset->isValid());
    }

    public function testIsValidReturnsFalseWhenAChildIsInvalid(): void
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
    // toHtml
    // --------------------------------------------------------------

    public function testToHtmlWrapsContentInFieldsetTag(): void
    {
        $fieldset = new Fieldset();

        $html = $fieldset->toHtml();

        $this->assertStringStartsWith('<fieldset', $html);
        $this->assertStringContainsString('</fieldset>', $html);
    }

    public function testToHtmlRendersLegendWhenHeaderSet(): void
    {
        $fieldset = new Fieldset('Personal Information');

        $html = $fieldset->toHtml();

        $this->assertStringContainsString('<legend>', $html);
        $this->assertStringContainsString('Personal Information', $html);
    }

    public function testToHtmlSkipsLegendWhenNoHeader(): void
    {
        $fieldset = new Fieldset();

        $html = $fieldset->toHtml();

        $this->assertStringNotContainsString('<legend>', $html);
    }

    public function testToHtmlIncludesNoteOutputWhenNotePresent(): void
    {
        $fieldset = new Fieldset('Title', 'Note Header', 'Note body');

        $html = $fieldset->toHtml();

        $this->assertStringContainsString('Note Header', $html);
        $this->assertStringContainsString('Note body', $html);
    }

    public function testToHtmlConcatenatesChildFieldOutput(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('marker-one', ValidForm::VFORM_STRING, 'One'));
        $fieldset->addField(new Text('marker-two', ValidForm::VFORM_STRING, 'Two'));

        $html = $fieldset->toHtml();

        // Field names appear in the rendered HTML inputs.
        $this->assertStringContainsString('marker-one', $html);
        $this->assertStringContainsString('marker-two', $html);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    public function testToJsConcatenatesChildJsOutput(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('alpha', ValidForm::VFORM_STRING, 'Alpha'));
        $fieldset->addField(new Text('beta', ValidForm::VFORM_STRING, 'Beta'));

        $js = $fieldset->toJS();

        // Each child contributes its JS — both field names should appear.
        $this->assertStringContainsString('alpha', $js);
        $this->assertStringContainsString('beta', $js);
    }

    public function testToJsForEmptyFieldsetIsString(): void
    {
        $fieldset = new Fieldset();

        $this->assertIsString($fieldset->toJS());
    }

    // --------------------------------------------------------------
    // getHeader / setHeader (magic via ClassDynamic)
    // --------------------------------------------------------------

    public function testHeaderRoundTripsViaMagicAccessors(): void
    {
        $fieldset = new Fieldset('Original');

        $fieldset->setHeader('Updated');

        $this->assertSame('Updated', $fieldset->getHeader());
    }
}
