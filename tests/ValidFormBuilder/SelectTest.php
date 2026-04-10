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
        unset($_REQUEST['colour']);
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
