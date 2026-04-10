<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * Shared HTML parsing helper for tests that need to make structural
 * assertions about rendered output.
 *
 * Use this trait whenever a test exercises a `toHtml()` method and wants
 * real DOM-level assertions (element counts, parent/child relationships,
 * attribute values, ordering) instead of brittle substring checks.
 *
 * Example:
 * ```php
 * class FooTest extends TestCase
 * {
 *     use HtmlAssertionsTrait;
 *
 *     public function testRendersInput(): void
 *     {
 *         $xpath = $this->parseHtml($foo->toHtml());
 *         $this->assertSame(1, $xpath->query('//input[@name="foo"]')->length);
 *     }
 * }
 * ```
 *
 * The libxml internal-error state is captured before each test and
 * restored after, via PHPUnit's `#[Before]` / `#[After]` lifecycle
 * attributes. Test classes do not need to declare or extend any
 * setUp/tearDown method to use this trait.
 *
 * @note On PHP 8.4+ the standard library ships a real HTML5 parser as
 *       `\Dom\HTMLDocument::createFromString()`, which avoids libxml's
 *       quirks (default ISO-8859-1 encoding, HTML5-as-XML errors, the
 *       implicit <html>/<body> wrap, etc.). When this project's
 *       `composer.json` raises `php` to `>=8.4`, swap this trait's
 *       implementation to:
 *
 *       ```php
 *       protected function parseHtml(string $html): \DOMXPath
 *       {
 *           $doc = \Dom\HTMLDocument::createFromString(
 *               $html,
 *               LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED,
 *               'utf-8'
 *           );
 *           return new \DOMXPath($doc);
 *       }
 *       ```
 *
 *       and drop the libxml lifecycle hooks entirely — the new parser
 *       reports errors through return values, not the libxml error
 *       buffer, so capture/restore becomes unnecessary.
 */
trait HtmlAssertionsTrait
{
    private bool $previousLibxmlInternalErrors;

    #[Before]
    protected function enableLibxmlInternalErrorCapture(): void
    {
        // HTML fragments produced by ValidFormBuilder are HTML5 (self-closed
        // <input>, etc.), which DOMDocument::loadHTML() reports as warnings
        // because it parses HTML4. Capture them into the libxml buffer so
        // they don't pollute test output.
        $this->previousLibxmlInternalErrors = libxml_use_internal_errors(true);
    }

    #[After]
    protected function restoreLibxmlInternalErrorState(): void
    {
        libxml_clear_errors();
        libxml_use_internal_errors($this->previousLibxmlInternalErrors);
    }

    /**
     * Parse an HTML fragment into a DOMXPath instance.
     *
     * The fragment is wrapped in a minimal HTML5 document with an explicit
     * UTF-8 meta charset so DOMDocument parses it as HTML5 instead of
     * falling back to ISO-8859-1 (its historical default for loadHTML).
     * XPath queries should use `//` (descendant-anywhere) to ignore the
     * implicit `<html>` / `<body>` wrap.
     */
    protected function parseHtml(string $html): \DOMXPath
    {
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        return new \DOMXPath($doc);
    }
}
