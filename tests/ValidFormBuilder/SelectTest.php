<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Select;
use ValidFormBuilder\SelectGroup;
use ValidFormBuilder\SelectOption;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Select}.
 *
 * Select renders a `<select>` dropdown with `<option>` and optional
 * `<optgroup>` children. It extends Element and manages an internal
 * options Collection.
 *
 * Security audit:
 * - SelectOption and SelectGroup previously rendered value/label without
 *   htmlspecialchars — fixed in this commit (#206).
 * - XSS regression tests verify the fix via DOM parse.
 */
class SelectTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['colour'], $_REQUEST['colour_dynamic'], $_REQUEST['rating']);
    }

    // --------------------------------------------------------------
    // Construction
    // --------------------------------------------------------------

    #[Test]
    public function addFieldWithSelectListTypeReturnsSelectInstance(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);

        $this->assertInstanceOf(Select::class, $select);
    }

    #[Test]
    public function constructorInitialisesEmptyOptionsCollection(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);

        $this->assertInstanceOf(Collection::class, $select->getOptions());
    }

    // --------------------------------------------------------------
    // addField / addGroup
    // --------------------------------------------------------------

    #[Test]
    public function addFieldCreatesSelectOptionAndReturnsIt(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $option = $select->addField('Red', 'red');

        $this->assertInstanceOf(SelectOption::class, $option);
        $this->assertSame(1, $select->getOptions()->count());
    }

    #[Test]
    public function addGroupCreatesSelectGroupAndReturnsIt(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $group = $select->addGroup('Primary colours');

        $this->assertInstanceOf(SelectGroup::class, $group);
        $this->assertSame(1, $select->getOptions()->count());
    }

    #[Test]
    public function addFieldSetsSelectAsParent(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $option = $select->addField('Red', 'red');

        $this->assertSame($select, $option->getMeta('parent'));
    }

    // --------------------------------------------------------------
    // toHtml
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersSelectElementWithOptions(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');
        $select->addField('Blue', 'blue');

        $xpath = $this->parseHtml($select->toHtml());

        // `//select` — the single <select> element.
        $selectEl = $xpath->query('//select')->item(0);
        $this->assertNotNull($selectEl);
        $this->assertSame('colour', $selectEl->getAttribute('name'));

        // `//select/option` — the option children of the select.
        $options = $xpath->query('//select/option');
        $this->assertSame(2, $options->length);
        $this->assertSame('red', $options->item(0)->getAttribute('value'));
        $this->assertSame('Red', trim($options->item(0)->textContent));
        $this->assertSame('blue', $options->item(1)->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersOptgroupWithNestedOptions(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $group = $select->addGroup('Primary');
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');
        $select->addField('Other', 'other');

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/optgroup` — the optgroup as a direct child of select.
        $optgroup = $xpath->query('//select/optgroup')->item(0);
        $this->assertNotNull($optgroup);
        $this->assertSame('Primary', $optgroup->getAttribute('label'));

        // `//select/optgroup/option` — options nested inside the optgroup.
        $groupOptions = $xpath->query('//select/optgroup/option');
        $this->assertSame(2, $groupOptions->length);

        // `//select/option` — the standalone option outside the optgroup.
        // Note: this query gets ALL options (including those in optgroup), so
        // count total options instead.
        $allOptions = $xpath->query('//select//option');
        $this->assertSame(3, $allOptions->length);
    }

    #[Test]
    public function toHtmlMarksSelectedOptionWithAttribute(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');
        $select->addField('Blue', 'blue', true);

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — check selected attribute.
        $options = $xpath->query('//select/option');
        $this->assertSame('', $options->item(0)->getAttribute('selected'));
        $this->assertSame('selected', $options->item(1)->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlRendersLabelLinkedToSelect(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml());

        // `//label[@for="colour"]` — label linked to the select by its for attribute.
        $label = $xpath->query('//label[@for="colour"]')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Colour', trim($label->textContent));
    }

    #[Test]
    public function toHtmlRequiredSelectWrapperHasRequiredClassToken(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            ['required' => true]
        );
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml());

        // `//div` — the outer wrapper <div> whose class reflects the field state.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function toHtmlRequiredSubmittedEmptyRendersErrorClassAndMessage(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            ['required' => true],
            ['required' => 'Colour is required']
        );
        $select->addField('Red', 'red');

        // Submitted without a value in $_REQUEST — the required check fails.
        $xpath = $this->parseHtml($select->toHtml(true));

        // `//div` — the outer state wrapper div.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);

        // `//div/p[contains(concat(" ", normalize-space(@class), " "), " vf__error ")]`
        // — the error <p> rendered inside the wrapper, matched on the whole
        // `vf__error` class token.
        $error = $xpath->query('//div/p[contains(concat(" ", normalize-space(@class), " "), " vf__error ")]')->item(0);
        $this->assertNotNull($error);
        $this->assertSame('Colour is required', trim($error->textContent));
    }

    #[Test]
    public function toHtmlWithoutLabelAddsNolabelClass(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml(false, false, false));

        // `//div` — the outer wrapper carries the vf__nolabel token.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__nolabel', $classTokens);

        // QUIRK: unlike Checkbox and File, Select still renders the <label>
        // element when $blnLabel is false — only the wrapper class changes.
        // This locks in the current behaviour.
        $this->assertSame(1, $xpath->query('//label[@for="colour"]')->length);
    }

    #[Test]
    public function toHtmlWithTipAppendsSmallTipElement(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['tip' => 'Pick your favourite']
        );
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml());

        // `//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]`
        // — a <small> whose class list contains the whole `vf__tip` token.
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Pick your favourite', trim($tip->textContent));
    }

    #[Test]
    public function toHtmlParsesRangesAtRenderTimeWhenOptionsCollectionIsEmpty(): void
    {
        $select = $this->form->addField(
            'rating',
            'Rating',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['start' => 1, 'end' => 3]
        );

        // The constructor already parsed the ranges; empty the collection again
        // so __toHtml() has to re-parse them at render time.
        $ref = new \ReflectionProperty(Select::class, '__options');
        $ref->setAccessible(true);
        $ref->setValue($select, new Collection());

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — the range was re-parsed into three options.
        $options = $xpath->query('//select/option');
        $this->assertSame(3, $options->length);
        $this->assertSame('1', $options->item(0)->getAttribute('value'));
    }

    // --------------------------------------------------------------
    // Range parsing (__parseRanges)
    // --------------------------------------------------------------

    #[Test]
    public function labelRangeWithMatchingValueRangePairsLabelsToValues(): void
    {
        $select = $this->form->addField(
            'rating',
            'Rating',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            [
                'labelRange' => ['Bad', 'Okay', 'Great'],
                'valueRange' => [1, 2, 3],
            ]
        );

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — three options pairing each label to its value.
        $options = $xpath->query('//select/option');
        $this->assertSame(3, $options->length);
        $this->assertSame('1', $options->item(0)->getAttribute('value'));
        $this->assertSame('Bad', trim($options->item(0)->textContent));
        $this->assertSame('3', $options->item(2)->getAttribute('value'));
        $this->assertSame('Great', trim($options->item(2)->textContent));
    }

    #[Test]
    public function labelRangeWithoutValueRangeUsesLabelsAsValues(): void
    {
        $select = $this->form->addField(
            'rating',
            'Rating',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['labelRange' => ['Bad', 'Great']]
        );

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — each label doubles as its own value.
        $options = $xpath->query('//select/option');
        $this->assertSame(2, $options->length);
        $this->assertSame('Bad', $options->item(0)->getAttribute('value'));
        $this->assertSame('Bad', trim($options->item(0)->textContent));
        $this->assertSame('Great', $options->item(1)->getAttribute('value'));
    }

    #[Test]
    public function startEndMetaGeneratesAscendingNumericOptions(): void
    {
        $select = $this->form->addField(
            'rating',
            'Rating',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['start' => 1, 'end' => 4]
        );

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — values 1 through 4 in ascending order.
        $options = $xpath->query('//select/option');
        $this->assertSame(4, $options->length);
        $this->assertSame('1', $options->item(0)->getAttribute('value'));
        $this->assertSame('4', $options->item(3)->getAttribute('value'));
    }

    #[Test]
    public function startEndMetaGeneratesDescendingNumericOptionsWhenStartIsLarger(): void
    {
        $select = $this->form->addField(
            'rating',
            'Rating',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['start' => 3, 'end' => 1]
        );

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — values 3 down to 1 in descending order.
        $options = $xpath->query('//select/option');
        $this->assertSame(3, $options->length);
        $this->assertSame('3', $options->item(0)->getAttribute('value'));
        $this->assertSame('1', $options->item(2)->getAttribute('value'));
    }

    // --------------------------------------------------------------
    // Dynamic fields
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlDynamicSelectRendersOriginalAndCloneWithDataAttributes(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another colour']
        );
        $select->addField('Red', 'red');

        // Simulate a submission where the client-side duplicated the field once.
        $_REQUEST['colour_dynamic'] = '1';

        $xpath = $this->parseHtml($select->toHtml());

        // `//select[@name="colour"]` / `//select[@name="colour_1"]` — original + clone.
        $this->assertSame(1, $xpath->query('//select[@name="colour"]')->length);
        $this->assertSame(1, $xpath->query('//select[@name="colour_1"]')->length);

        // `//div[@data-dynamic="original"]` — the original wrapper is marked as such.
        $this->assertSame(1, $xpath->query('//div[@data-dynamic="original"]')->length);

        // `//div[@data-dynamic="clone"]` — the clone wrapper carries the vf__clone token.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);
        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);

        // `//div[@class="vf__dynamic"]/a` — the "add another" anchor after the last clone.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('Add another colour', trim($anchor->textContent));
        $this->assertSame('colour', $anchor->getAttribute('data-target-name'));
    }

    #[Test]
    public function toHtmlRemovableSelectRendersRemoveLabelAnchor(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['dynamic' => true, 'dynamicRemoveLabel' => 'Remove colour']
        );
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml());

        // `//div` — the wrapper carries the vf__removable token.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);

        // `//a[contains(concat(" ", normalize-space(@class), " "), " vf__removeLabel ")]`
        // — the remove anchor with its dedicated class token.
        $anchor = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " vf__removeLabel ")]')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('Remove colour', trim($anchor->textContent));
    }

    // --------------------------------------------------------------
    // Simple layout (MultiField item rendering)
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlSimpleLayoutAddsMultifielditemClassAndOmitsLabel(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml(false, true));

        // `//div` — the simple-layout wrapper.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__multifielditem', $classTokens);

        // `//label` — simple layout never renders a label.
        $this->assertSame(0, $xpath->query('//label')->length);
    }

    #[Test]
    public function toHtmlSimpleLayoutSubmittedEmptyAddsErrorClass(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            ['required' => true]
        );
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml(true, true));

        // `//div` — the simple-layout wrapper carries the vf__error token.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);
    }

    #[Test]
    public function toHtmlSimpleLayoutRemovableAddsRemovableClass(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['dynamic' => true, 'dynamicRemoveLabel' => 'Remove colour']
        );
        $select->addField('Red', 'red');

        $xpath = $this->parseHtml($select->toHtml(false, true));

        // `//div` — the simple-layout wrapper carries the vf__removable token.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);
    }

    #[Test]
    public function toHtmlSimpleLayoutDynamicRendersOriginalAndCloneWrappers(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another colour']
        );
        $select->addField('Red', 'red');

        $_REQUEST['colour_dynamic'] = '1';

        $xpath = $this->parseHtml($select->toHtml(false, true));

        // `//div[@data-dynamic="original"]` — the original simple-layout wrapper.
        $this->assertSame(1, $xpath->query('//div[@data-dynamic="original"]')->length);

        // `//div[@data-dynamic="clone"]` — the clone wrapper with the vf__clone token.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);
        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsAddElementCall(): void
    {
        $select = $this->form->addField('colour', 'Colour', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Red', 'red');

        $js = $select->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'colour'", $js);
    }

    #[Test]
    public function toJsDynamicSelectEmitsAddElementCallPerDynamicCount(): void
    {
        $select = $this->form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            ['required' => true],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another colour']
        );
        $select->addField('Red', 'red');

        // Simulate a submission where the client-side duplicated the field once.
        $_REQUEST['colour_dynamic'] = '1';

        $js = $select->toJS();

        // One addElement call for the original and one for the clone.
        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'colour'", $js);
        $this->assertStringContainsString("'colour_1'", $js);

        // As soon as clones exist, the required flag (fourth positional slot)
        // is forced to false — for the clone as well as the original.
        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('colour',\\s*'colour',[^,]+,\\s*false,/",
            $js
        );
        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('colour_1',\\s*'colour_1',[^,]+,\\s*false,/",
            $js
        );
    }

    // --------------------------------------------------------------
    // Security — XSS regression for #206
    // --------------------------------------------------------------

    #[Test]
    public function optionValueAndLabelAreEscapedInRenderedHtml(): void
    {
        // SECURITY regression for #206: SelectOption must escape value
        // (attribute context) and label (text content) via htmlspecialchars.
        $select = $this->form->addField('xss', 'XSS', ValidForm::VFORM_SELECT_LIST);
        $select->addField('Normal', 'safe');
        $select->addField('Attack', '"><script>alert(1)</script>');

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/option` — the two options.
        $options = $xpath->query('//select/option');
        $this->assertSame(2, $options->length);

        // Attack value is preserved as a literal attribute value, not injected HTML.
        $this->assertSame('"><script>alert(1)</script>', $options->item(1)->getAttribute('value'));
        // `//script` — no injected script elements.
        $this->assertSame(0, $xpath->query('//script')->length);
    }

    #[Test]
    public function optgroupLabelIsEscapedInRenderedHtml(): void
    {
        // SECURITY regression for #206: SelectGroup must escape label in the
        // optgroup's label attribute.
        $select = $this->form->addField('xss', 'XSS', ValidForm::VFORM_SELECT_LIST);
        $group = $select->addGroup('" onmouseover="alert(1)');
        $group->addField('Option', 'opt');

        $xpath = $this->parseHtml($select->toHtml());

        // `//select/optgroup` — the optgroup element.
        $optgroup = $xpath->query('//select/optgroup')->item(0);
        $this->assertNotNull($optgroup);

        // The label should be the literal escaped payload, not an onmouseover attribute.
        $this->assertSame('" onmouseover="alert(1)', $optgroup->getAttribute('label'));
        $this->assertSame('', $optgroup->getAttribute('onmouseover'));
    }
}
