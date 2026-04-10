<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Note;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Note}.
 *
 * Note renders a developer-authored help / instruction block inside a
 * form fieldset. It produces a `<div class="vf__notes">` with an optional
 * `<h4>` header and a body that is either wrapped in `<p>` automatically
 * or rendered raw if it already contains `<p>` tags.
 *
 * Surface covered:
 * - Constructor: stores header, body, meta.
 * - toHtml: wrapper div with vf__notes class, conditional header in <h4>,
 *   body auto-wrapped in <p> vs raw pass-through when <p> already present.
 * - Edge cases: empty header, empty body, body with existing <p> tags.
 *
 * Security audit:
 * - Header and body are rendered as raw HTML (no htmlspecialchars). This is
 *   by design — Note is a developer-authored content block, not a
 *   data-display element. The class name, the internal @internal tag, and
 *   the `<p>`-detection logic all indicate the developer is expected to
 *   provide safe HTML. Documented here as an intentional contract.
 */
class NoteTest extends TestCase
{
    use HtmlAssertionsTrait;

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresHeaderAndBody(): void
    {
        $note = new Note('Important', 'Read carefully.');

        // Access via ClassDynamic magic getters.
        $this->assertSame('Important', $note->getHeader());
        $this->assertSame('Read carefully.', $note->getBody());
    }

    #[Test]
    public function constructorAcceptsNullHeaderAndBody(): void
    {
        $note = new Note();

        $this->assertNull($note->getHeader());
        $this->assertNull($note->getBody());
    }

    // --------------------------------------------------------------
    // toHtml — structural rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersWrapperDivWithNotesClass(): void
    {
        $note = new Note('Title', 'Body');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div[contains(concat(" ", normalize-space(@class), " "), " vf__notes ")]`
        // — the outer wrapper div whose class list includes `vf__notes`.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__notes ")]')->item(0);
        $this->assertNotNull($wrapper);
    }

    #[Test]
    public function toHtmlRendersHeaderInH4WhenProvided(): void
    {
        $note = new Note('Important notice');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/h4` — <h4> as a direct child of the wrapper div.
        $h4 = $xpath->query('//div/h4')->item(0);
        $this->assertNotNull($h4);
        $this->assertSame('Important notice', trim($h4->textContent));
    }

    #[Test]
    public function toHtmlSkipsH4WhenHeaderIsEmpty(): void
    {
        $note = new Note(null, 'Just a body.');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/h4` — expect zero <h4> elements.
        $this->assertSame(0, $xpath->query('//div/h4')->length);
    }

    #[Test]
    public function toHtmlWrapsPlainBodyTextInParagraph(): void
    {
        $note = new Note(null, 'Plain text body.');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/p` — the body should be wrapped in a <p> element because the
        // raw body does not contain a `<p>` tag.
        $p = $xpath->query('//div/p')->item(0);
        $this->assertNotNull($p);
        $this->assertSame('Plain text body.', trim($p->textContent));
    }

    #[Test]
    public function toHtmlRendersBodyRawWhenItAlreadyContainsParagraphTags(): void
    {
        // When the body already includes `<p>` tags, the Note renders it as-is
        // without wrapping in an additional `<p>`.
        $body = '<p class="intro">First paragraph.</p><p>Second paragraph.</p>';
        $note = new Note(null, $body);

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/p` — expect two <p> elements (from the raw body), not three
        // (which would happen if the body were double-wrapped in a <p>).
        $paragraphs = $xpath->query('//div/p');
        $this->assertSame(2, $paragraphs->length);
        $this->assertSame('First paragraph.', trim($paragraphs->item(0)->textContent));
        $this->assertSame('Second paragraph.', trim($paragraphs->item(1)->textContent));
    }

    #[Test]
    public function toHtmlRendersNothingForBodyWhenBodyIsEmpty(): void
    {
        $note = new Note('Header only');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/p` — no body means no <p> element.
        $this->assertSame(0, $xpath->query('//div/p')->length);
    }

    #[Test]
    public function toHtmlRendersHeaderAndBodyTogether(): void
    {
        $note = new Note('Title', 'Body text');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/h4` — header present.
        $this->assertSame(1, $xpath->query('//div/h4')->length);
        // `//div/p` — body wrapped in <p>.
        $this->assertSame(1, $xpath->query('//div/p')->length);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function headerAndBodyRenderAsRawHtmlByDesign(): void
    {
        // NOTE: Note is a developer-authored content block. Both header and body
        // are rendered as raw HTML without htmlspecialchars(). This is intentional:
        // the developer may include formatting (<strong>, <a>, <em>) in the note.
        // The body's <p>-detection regex further confirms this expectation.
        //
        // Developers must never pass user input to Note's constructor. If they do,
        // it's an XSS footgun — same as Navigation::addHtml() and StaticText.
        $note = new Note('<em>Warning</em>', '<a href="#">Click here</a>');

        $xpath = $this->parseHtml($note->toHtml());

        // `//div/h4/em` — the <em> in the header renders as a real HTML element.
        $em = $xpath->query('//div/h4/em')->item(0);
        $this->assertNotNull($em);
        $this->assertSame('Warning', $em->textContent);

        // `//div/p/a` — the <a> in the body renders as a real clickable link
        // (the body doesn't contain <p>, so it gets auto-wrapped in one first).
        $a = $xpath->query('//div/p/a')->item(0);
        $this->assertNotNull($a);
        $this->assertSame('#', $a->getAttribute('href'));
    }
}
