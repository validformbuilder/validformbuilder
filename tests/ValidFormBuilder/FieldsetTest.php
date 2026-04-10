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
    // toHtml — structural assertions via DOMXPath
    // --------------------------------------------------------------

    public function testToHtmlRendersExactlyOneFieldsetElement(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(1, $xpath->query('//fieldset')->length);
    }

    public function testToHtmlFieldsetCarriesGeneratedIdAttribute(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());
        $node = $xpath->query('//fieldset')->item(0);

        $this->assertNotNull($node);
        $this->assertNotEmpty($node->getAttribute('id'));
        $this->assertStringStartsWith('fieldset_', $node->getAttribute('id'));
    }

    public function testToHtmlRendersLegendWithHeaderAsChildOfFieldset(): void
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

    public function testToHtmlDoesNotRenderLegendWhenHeaderIsMissing(): void
    {
        $fieldset = new Fieldset();

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(0, $xpath->query('//fieldset/legend')->length);
    }

    public function testToHtmlEmbedsNoteAsChildOfFieldsetWithCorrectStructure(): void
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

    public function testToHtmlDoesNotEmbedNoteWhenNoteArgsAreEmpty(): void
    {
        $fieldset = new Fieldset('Title');

        $xpath = $this->parseHtml($fieldset->toHtml());

        $this->assertSame(
            0,
            $xpath->query('//fieldset/div[contains(concat(" ", normalize-space(@class), " "), " vf__notes ")]')->length
        );
    }

    public function testToHtmlRendersEachChildFieldAsInputDescendantOfFieldset(): void
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

    public function testToHtmlPreservesChildOrder(): void
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

    public function testToHtmlRendersChildLabelsLinkedToInputs(): void
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

    public function testToJsEmitsAddElementCallForEachChild(): void
    {
        $fieldset = new Fieldset();
        $fieldset->addField(new Text('alpha', ValidForm::VFORM_STRING, 'Alpha'));
        $fieldset->addField(new Text('beta', ValidForm::VFORM_STRING, 'Beta'));

        $js = $fieldset->toJS();

        // Each Text child emits exactly one objForm.addElement('<name>', ...) call.
        $this->assertSame(1, substr_count($js, "objForm.addElement('alpha'"));
        $this->assertSame(1, substr_count($js, "objForm.addElement('beta'"));
    }

    public function testToJsPreservesChildOrder(): void
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

    public function testToJsForEmptyFieldsetReturnsEmptyString(): void
    {
        $fieldset = new Fieldset();

        $this->assertSame('', $fieldset->toJS());
    }

    // --------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------

    /**
     * Parse a Fieldset HTML fragment into a DOMXPath instance.
     *
     * The fragment is wrapped in a minimal HTML5 document with a UTF-8
     * meta charset so DOMDocument parses it as HTML5 instead of falling
     * back to ISO-8859-1. libxml errors are swallowed because the
     * fragment is HTML5 (self-closed inputs etc.) rather than strict XML.
     * XPath queries use // (descendant-anywhere) so the implicit
     * <html>/<body> wrap is irrelevant.
     */
    private function parseHtml(string $html): \DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($doc);
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
