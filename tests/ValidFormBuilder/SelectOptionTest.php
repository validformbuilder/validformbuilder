<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\SelectOption;

/**
 * Coverage for {@link \ValidFormBuilder\SelectOption}.
 *
 * SelectOption renders a single `<option>` element. It extends Element
 * and stores its own label, value, and selected state.
 *
 * Security audit:
 * - Value (attribute context) and label (text content) are rendered
 *   via htmlspecialchars (#206). XSS regression tests verify the fix.
 */
class SelectOptionTest extends TestCase
{
    use HtmlAssertionsTrait;

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresLabelValueAndSelectedState(): void
    {
        $option = new SelectOption('Red', 'red', true);

        $labelRef = new \ReflectionProperty(SelectOption::class, '__label');
        $labelRef->setAccessible(true);
        $valueRef = new \ReflectionProperty(SelectOption::class, '__value');
        $valueRef->setAccessible(true);
        $selectedRef = new \ReflectionProperty(SelectOption::class, '__selected');
        $selectedRef->setAccessible(true);

        $this->assertSame('Red', $labelRef->getValue($option));
        $this->assertSame('red', $valueRef->getValue($option));
        $this->assertTrue($selectedRef->getValue($option));
    }

    #[Test]
    public function constructorDefaultsToNotSelected(): void
    {
        $option = new SelectOption('Blue', 'blue');

        $ref = new \ReflectionProperty(SelectOption::class, '__selected');
        $ref->setAccessible(true);
        $this->assertFalse($ref->getValue($option));
    }

    #[Test]
    public function constructorHandlesNullMeta(): void
    {
        // Passing null as meta should not trigger an error — constructor
        // normalises it to an empty array.
        $option = new SelectOption('Green', 'green', false, null);

        $ref = new \ReflectionProperty(SelectOption::class, '__meta');
        $ref->setAccessible(true);
        $this->assertSame([], $ref->getValue($option));
    }

    #[Test]
    public function constructorStoresMetaArray(): void
    {
        $meta = ['data-extra' => 'info'];
        $option = new SelectOption('Yellow', 'yellow', false, $meta);

        $ref = new \ReflectionProperty(SelectOption::class, '__meta');
        $ref->setAccessible(true);
        $this->assertSame($meta, $ref->getValue($option));
    }

    // --------------------------------------------------------------
    // getValue
    // --------------------------------------------------------------

    #[Test]
    public function getValueReturnsConstructorValue(): void
    {
        $option = new SelectOption('Red', 'red');

        $this->assertSame('red', $option->getValue());
    }

    #[Test]
    public function getValueIgnoresDynamicPositionParameter(): void
    {
        $option = new SelectOption('Red', 'red');

        // getValue() on SelectOption always returns its own value,
        // regardless of dynamic position.
        $this->assertSame('red', $option->getValue(5));
    }

    // --------------------------------------------------------------
    // toHtmlInternal
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlInternalRendersOptionWithValueAndLabel(): void
    {
        $option = new SelectOption('Red', 'red');

        $xpath = $this->parseHtml($option->toHtmlInternal());

        // `//option` — the rendered option element.
        $el = $xpath->query('//option')->item(0);
        $this->assertNotNull($el);
        $this->assertSame('red', $el->getAttribute('value'));
        $this->assertSame('Red', trim($el->textContent));
    }

    #[Test]
    public function toHtmlInternalMarksSelectedWhenFlagIsTrue(): void
    {
        $option = new SelectOption('Red', 'red', true);

        $xpath = $this->parseHtml($option->toHtmlInternal());

        // `//option` — should have the selected attribute.
        $el = $xpath->query('//option')->item(0);
        $this->assertSame('selected', $el->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlInternalNotSelectedByDefault(): void
    {
        $option = new SelectOption('Red', 'red');

        $xpath = $this->parseHtml($option->toHtmlInternal());

        // `//option` — should NOT have the selected attribute.
        $el = $xpath->query('//option')->item(0);
        $this->assertSame('', $el->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlInternalSelectsWhenPassedValueMatches(): void
    {
        $option = new SelectOption('Blue', 'blue');

        // Passing 'blue' as value should mark this option as selected.
        $xpath = $this->parseHtml($option->toHtmlInternal('blue'));

        // `//option` — should have the selected attribute via value match.
        $el = $xpath->query('//option')->item(0);
        $this->assertSame('selected', $el->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlInternalDoesNotSelectWhenPassedValueDiffers(): void
    {
        $option = new SelectOption('Blue', 'blue');

        $xpath = $this->parseHtml($option->toHtmlInternal('red'));

        // `//option` — should NOT be selected since 'red' != 'blue'.
        $el = $xpath->query('//option')->item(0);
        $this->assertSame('', $el->getAttribute('selected'));
    }

    #[Test]
    public function toHtmlInternalSelectedFlagIgnoredWhenValueProvided(): void
    {
        // When a value is explicitly passed and it does NOT match this
        // option's value, the selected=true flag from the constructor
        // is overridden — only the value match matters.
        $option = new SelectOption('Red', 'red', true);

        $xpath = $this->parseHtml($option->toHtmlInternal('blue'));

        // `//option` — value match takes precedence; 'blue' != 'red',
        // but the constructor flag is suppressed because $value is non-null.
        $el = $xpath->query('//option')->item(0);

        // The constructor selected flag only applies when $value is null.
        // When a value IS passed, selection is determined by value match only.
        // 'blue' != 'red' so this should not be selected.
        $this->assertSame('', $el->getAttribute('selected'));
    }

    // --------------------------------------------------------------
    // Security — XSS regression for #206
    // --------------------------------------------------------------

    #[Test]
    public function valueIsEscapedInAttribute(): void
    {
        // SECURITY regression for #206: value must be escaped in the
        // attribute context via htmlspecialchars.
        $option = new SelectOption('Attack', '"><script>alert(1)</script>');

        $xpath = $this->parseHtml($option->toHtmlInternal());

        // `//option` — the option element.
        $el = $xpath->query('//option')->item(0);
        // The value is preserved as a literal attribute, not injected HTML.
        $this->assertSame('"><script>alert(1)</script>', $el->getAttribute('value'));

        // `//script` — no injected script elements.
        $this->assertSame(0, $xpath->query('//script')->length);
    }

    #[Test]
    public function labelIsEscapedInTextContent(): void
    {
        // SECURITY regression for #206: label must be escaped in the
        // text-node context via htmlspecialchars.
        $option = new SelectOption('<img src=x onerror=alert(1)>', 'safe');

        $xpath = $this->parseHtml($option->toHtmlInternal());

        // `//img` — no injected img elements inside the option.
        $this->assertSame(0, $xpath->query('//option//img')->length);
    }
}
