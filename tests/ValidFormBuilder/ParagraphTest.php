<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Paragraph;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Paragraph}.
 *
 * Paragraph renders a developer-authored informational block inside a form.
 * It produces a `<div>` with optional `<h3>` header and auto-`<p>`-wrapped body.
 *
 * Security audit: header and body rendered as raw HTML — intentional for
 * developer-authored content (same pattern as Note). No new vulnerabilities.
 */
class ParagraphTest extends TestCase
{
    use HtmlAssertionsTrait;

    #[Test]
    public function constructorStoresHeaderBodyAndMeta(): void
    {
        $p = new Paragraph('Title', 'Body text');

        $this->assertSame('Title', $p->getHeader());
        $this->assertSame('Body text', $p->getBody());
    }

    #[Test]
    public function toHtmlRendersWrapperDivWithH3HeaderAndPBody(): void
    {
        $p = new Paragraph('Title', 'Body text');
        $xpath = $this->parseHtml($p->toHtml());

        // `//div/h3` — header as direct child of wrapper.
        $h3 = $xpath->query('//div/h3')->item(0);
        $this->assertNotNull($h3);
        $this->assertSame('Title', trim($h3->textContent));

        // `//div/p` — body wrapped in <p> since it doesn't contain <p> tags.
        $body = $xpath->query('//div/p')->item(0);
        $this->assertNotNull($body);
        $this->assertSame('Body text', trim($body->textContent));
    }

    #[Test]
    public function toHtmlSkipsH3WhenHeaderIsEmpty(): void
    {
        $p = new Paragraph(null, 'Just body');
        $xpath = $this->parseHtml($p->toHtml());

        // `//div/h3` — no header → no h3.
        $this->assertSame(0, $xpath->query('//div/h3')->length);
    }

    #[Test]
    public function toHtmlRendersBodyRawWhenItAlreadyContainsParagraphTags(): void
    {
        $body = '<p>First.</p><p>Second.</p>';
        $p = new Paragraph(null, $body);
        $xpath = $this->parseHtml($p->toHtml());

        // `//div/p` — two <p> elements from the raw body, not three.
        $this->assertSame(2, $xpath->query('//div/p')->length);
    }

    #[Test]
    public function toHtmlSkipsBodyContentWhenBodyIsEmpty(): void
    {
        $p = new Paragraph('Header only');
        $xpath = $this->parseHtml($p->toHtml());

        // `//div/p` — no body → no <p>.
        $this->assertSame(0, $xpath->query('//div/p')->length);
    }

    #[Test]
    public function isValidAlwaysReturnsTrue(): void
    {
        $this->assertTrue((new Paragraph('T', 'B'))->isValid());
    }

    #[Test]
    public function getValueReturnsNull(): void
    {
        $this->assertNull((new Paragraph())->getValue());
    }

    #[Test]
    public function hasFieldsReturnsFalse(): void
    {
        $this->assertFalse((new Paragraph())->hasFields());
    }

    #[Test]
    public function getTypeReturnsParagraphConstant(): void
    {
        $this->assertSame(ValidForm::VFORM_PARAGRAPH, (new Paragraph())->getType());
    }

    #[Test]
    public function isDynamicReturnsFalse(): void
    {
        $this->assertFalse((new Paragraph())->isDynamic());
    }

    #[Test]
    public function headerAndBodyRenderAsRawHtmlByDesign(): void
    {
        // Same intentional raw-HTML contract as Note. Developer content, not user input.
        $p = new Paragraph('<em>Note</em>', '<a href="#">Link</a>');
        $xpath = $this->parseHtml($p->toHtml());

        // `//div/h3/em` — HTML in header renders as real elements.
        $this->assertNotNull($xpath->query('//div/h3/em')->item(0));
    }
}
