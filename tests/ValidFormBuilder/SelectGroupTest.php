<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Collection;
use ValidFormBuilder\SelectGroup;
use ValidFormBuilder\SelectOption;

/**
 * Coverage for {@link \ValidFormBuilder\SelectGroup}.
 *
 * SelectGroup renders an `<optgroup>` containing one or more
 * `<option>` children. It extends Base and manages an internal
 * options Collection.
 *
 * Security audit:
 * - The label attribute is rendered via htmlspecialchars (#206).
 *   XSS regression tests verify the fix via DOM parse.
 */
class SelectGroupTest extends TestCase
{
    use HtmlAssertionsTrait;

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresLabel(): void
    {
        $group = new SelectGroup('Primary colours');

        $ref = new \ReflectionProperty(SelectGroup::class, '__label');
        $ref->setAccessible(true);
        $this->assertSame('Primary colours', $ref->getValue($group));
    }

    #[Test]
    public function constructorInitialisesEmptyOptionsCollection(): void
    {
        $group = new SelectGroup('Sizes');

        $ref = new \ReflectionProperty(SelectGroup::class, '__options');
        $ref->setAccessible(true);
        $options = $ref->getValue($group);

        $this->assertInstanceOf(Collection::class, $options);
        $this->assertSame(0, $options->count());
    }

    // --------------------------------------------------------------
    // addField
    // --------------------------------------------------------------

    #[Test]
    public function addFieldCreatesSelectOptionAndReturnsIt(): void
    {
        $group = new SelectGroup('Colours');
        $option = $group->addField('Red', 'red');

        $this->assertInstanceOf(SelectOption::class, $option);
    }

    #[Test]
    public function addFieldAddsOptionToInternalCollection(): void
    {
        $group = new SelectGroup('Colours');
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        $ref = new \ReflectionProperty(SelectGroup::class, '__options');
        $ref->setAccessible(true);
        $this->assertSame(2, $ref->getValue($group)->count());
    }

    #[Test]
    public function addFieldSetsGroupAsParentOnOption(): void
    {
        $group = new SelectGroup('Colours');
        $option = $group->addField('Red', 'red');

        $this->assertSame($group, $option->getMeta('parent'));
    }

    #[Test]
    public function addFieldPassesSelectedFlagToOption(): void
    {
        $group = new SelectGroup('Colours');
        $option = $group->addField('Red', 'red', true);

        // Verify the selected flag via reflection on SelectOption.
        $ref = new \ReflectionProperty(SelectOption::class, '__selected');
        $ref->setAccessible(true);
        $this->assertTrue($ref->getValue($option));
    }

    // --------------------------------------------------------------
    // toHtmlInternal
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlInternalRendersOptgroupWithLabelAttribute(): void
    {
        $group = new SelectGroup('Primary');
        $group->addField('Red', 'red');

        $xpath = $this->parseHtml($group->toHtmlInternal());

        // `//optgroup` — the rendered optgroup element.
        $optgroup = $xpath->query('//optgroup')->item(0);
        $this->assertNotNull($optgroup);
        $this->assertSame('Primary', $optgroup->getAttribute('label'));
    }

    #[Test]
    public function toHtmlInternalRendersNestedOptions(): void
    {
        $group = new SelectGroup('Sizes');
        $group->addField('Small', 's');
        $group->addField('Medium', 'm');
        $group->addField('Large', 'l');

        $xpath = $this->parseHtml($group->toHtmlInternal());

        // `//optgroup/option` — option children inside the optgroup.
        $options = $xpath->query('//optgroup/option');
        $this->assertSame(3, $options->length);
        $this->assertSame('s', $options->item(0)->getAttribute('value'));
        $this->assertSame('Small', trim($options->item(0)->textContent));
        $this->assertSame('l', $options->item(2)->getAttribute('value'));
    }

    #[Test]
    public function toHtmlInternalPassesValueToOptionsForSelection(): void
    {
        $group = new SelectGroup('Colours');
        $group->addField('Red', 'red');
        $group->addField('Blue', 'blue');

        // Pass 'blue' as the current value — it should be marked selected.
        $xpath = $this->parseHtml($group->toHtmlInternal('blue'));

        // `//optgroup/option` — check selected attributes.
        $options = $xpath->query('//optgroup/option');
        $this->assertSame('', $options->item(0)->getAttribute('selected'));
        $this->assertSame('selected', $options->item(1)->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlInternalWithNoOptionsRendersEmptyOptgroup(): void
    {
        $group = new SelectGroup('Empty');

        $xpath = $this->parseHtml($group->toHtmlInternal());

        // `//optgroup` — the optgroup should exist with no option children.
        $optgroup = $xpath->query('//optgroup')->item(0);
        $this->assertNotNull($optgroup);
        $this->assertSame(0, $xpath->query('//optgroup/option')->length);
    }

    // --------------------------------------------------------------
    // Security — XSS regression for #206
    // --------------------------------------------------------------

    #[Test]
    public function labelIsEscapedInOptgroupAttribute(): void
    {
        // SECURITY regression for #206: SelectGroup must escape the label
        // in the optgroup's label attribute via htmlspecialchars.
        $group = new SelectGroup('" onmouseover="alert(1)');
        $group->addField('Option', 'opt');

        $xpath = $this->parseHtml($group->toHtmlInternal());

        // `//optgroup` — the optgroup element.
        $optgroup = $xpath->query('//optgroup')->item(0);
        $this->assertNotNull($optgroup);

        // The label should be the literal escaped payload, not an event handler.
        $this->assertSame('" onmouseover="alert(1)', $optgroup->getAttribute('label'));
        $this->assertSame('', $optgroup->getAttribute('onmouseover'));
    }

    #[Test]
    public function htmlEntitiesInLabelAreEscaped(): void
    {
        // SECURITY: angle brackets and ampersands in group label must not
        // break out of the attribute context.
        $group = new SelectGroup('<script>alert("xss")</script>');
        $group->addField('Option', 'opt');

        $xpath = $this->parseHtml($group->toHtmlInternal());

        // `//optgroup` — verify no script injection.
        $this->assertSame(0, $xpath->query('//script')->length);
        $optgroup = $xpath->query('//optgroup')->item(0);
        $this->assertNotNull($optgroup);
    }
}
