<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Fieldset;
use ValidFormBuilder\Page;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Page}.
 *
 * Page is a wizard-step container used by ValidWizard. It holds fieldsets
 * and auto-wraps bare fields in a default fieldset when needed.
 *
 * Surface covered:
 * - Constructor: id (auto-generated or explicit), header, class/style meta,
 *   overview flag, empty elements collection.
 * - addField(): Fieldset pass-through vs auto-wrapping in a default Fieldset,
 *   dynamic counter injection.
 * - toHtml: wrapper div with vf__page class + id + optional style, optional
 *   h2 header, child elements (skipped in overview mode).
 * - toJS: objForm.addPage() + child JS.
 * - isValid / __validate: loops elements.
 * - hasFields / getFields / isDynamic / getShortHeader / getRandomId.
 *
 * Security audit:
 * - Header rendered as raw HTML — intentional for developer-authored content.
 * - No direct user input handling. No new vulnerabilities found.
 */
class PageTest extends TestCase
{
    use HtmlAssertionsTrait;

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresHeaderAndExplicitId(): void
    {
        $page = new Page('step-1', 'Personal Info');

        $this->assertSame('Personal Info', $page->getHeader());
        $this->assertSame('step-1', $page->getId());
    }

    #[Test]
    public function constructorAutoGeneratesIdWhenEmpty(): void
    {
        $page = new Page('', 'Step');

        $this->assertStringStartsWith('vf__page_', $page->getId());
    }

    #[Test]
    public function constructorInitialisesEmptyElementsCollection(): void
    {
        $page = new Page('p1');

        $this->assertInstanceOf(Collection::class, $page->getFields());
        $this->assertSame(0, $page->getFields()->count());
    }

    #[Test]
    public function constructorReadsClassAndStyleFromMeta(): void
    {
        $page = new Page('p1', 'Title', [
            'class' => 'custom-page',
            'style' => 'display:none',
        ]);

        $xpath = $this->parseHtml($page->toHtml());

        // `//div[contains(concat(" ", normalize-space(@class), " "), " vf__page ")]`
        // — the wrapper div with the vf__page token in its class list.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__page ")]')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('custom-page', $classTokens);
        $this->assertSame('display:none', $wrapper->getAttribute('style'));
    }

    // --------------------------------------------------------------
    // addField
    // --------------------------------------------------------------

    #[Test]
    public function addFieldWithFieldsetAddsItDirectlyToElements(): void
    {
        $page = new Page('p1');
        $fieldset = new Fieldset('Section A');

        $page->addField($fieldset);

        $this->assertSame(1, $page->getFields()->count());
        $this->assertSame($fieldset, $page->getFields()->getFirst());
    }

    #[Test]
    public function addFieldWithNonFieldsetAutoWrapsInDefaultFieldset(): void
    {
        $page = new Page('p1');
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $page->addField($field);

        // A default Fieldset was created automatically.
        $this->assertSame(1, $page->getFields()->count());
        $this->assertInstanceOf(Fieldset::class, $page->getFields()->getFirst());

        // The field lives inside that auto-created fieldset.
        $autoFieldset = $page->getFields()->getFirst();
        $this->assertSame(1, $autoFieldset->getFields()->count());
        $this->assertSame($field, $autoFieldset->getFields()->getFirst());
    }

    #[Test]
    public function addFieldReusesLastFieldsetForSubsequentNonFieldsetFields(): void
    {
        $page = new Page('p1');
        $first = new Text('first', ValidForm::VFORM_STRING, 'First');
        $second = new Text('second', ValidForm::VFORM_STRING, 'Second');

        $page->addField($first);
        $page->addField($second);

        // Still only one auto-created Fieldset.
        $this->assertSame(1, $page->getFields()->count());

        $autoFieldset = $page->getFields()->getFirst();
        $this->assertSame(2, $autoFieldset->getFields()->count());
    }

    // --------------------------------------------------------------
    // toHtml
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersWrapperDivWithPageClassAndId(): void
    {
        $page = new Page('step-1', 'Title');

        $xpath = $this->parseHtml($page->toHtml());

        // `//div[contains(concat(" ", normalize-space(@class), " "), " vf__page ")]`
        // — the wrapper div with vf__page in its class list.
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " vf__page ")]')->item(0);
        $this->assertNotNull($wrapper);
        $this->assertSame('step-1', $wrapper->getAttribute('id'));
    }

    #[Test]
    public function toHtmlRendersH2HeaderWhenProvided(): void
    {
        $page = new Page('p1', 'Personal Information');

        $xpath = $this->parseHtml($page->toHtml());

        // `//div/h2` — the <h2> as a direct child of the wrapper div.
        $h2 = $xpath->query('//div/h2')->item(0);
        $this->assertNotNull($h2);
        $this->assertSame('Personal Information', trim($h2->textContent));
    }

    #[Test]
    public function toHtmlSkipsH2WhenHeaderIsEmpty(): void
    {
        $page = new Page('p1', '');

        $xpath = $this->parseHtml($page->toHtml());

        // `//div/h2` — expect zero h2 elements.
        $this->assertSame(0, $xpath->query('//div/h2')->length);
    }

    #[Test]
    public function toHtmlRendersChildFieldsetsInsideWrapper(): void
    {
        $page = new Page('p1');
        $page->addField(new Fieldset('Section'));

        $xpath = $this->parseHtml($page->toHtml());

        // `//div//fieldset` — child fieldset rendered as a descendant of the page wrapper.
        $this->assertSame(1, $xpath->query('//div//fieldset')->length);
    }

    #[Test]
    public function toHtmlSkipsChildRenderingInOverviewMode(): void
    {
        $page = new Page('p1', 'Overview', ['overview' => true]);
        $page->addField(new Fieldset('Should not render'));

        $xpath = $this->parseHtml($page->toHtml());

        // `//div//fieldset` — overview pages skip children, so zero fieldsets.
        $this->assertSame(0, $xpath->query('//div//fieldset')->length);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsAddPageCall(): void
    {
        $page = new Page('step-1');

        $js = $page->toJS();

        $this->assertStringContainsString("objForm.addPage('step-1')", $js);
    }

    // --------------------------------------------------------------
    // isValid / hasFields / isDynamic / getShortHeader
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueForEmptyPage(): void
    {
        $page = new Page('p1');

        $this->assertTrue($page->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenAChildFieldIsInvalid(): void
    {
        $page = new Page('p1');
        $fieldset = new Fieldset();
        $fieldset->addField(new Text(
            'required-field',
            ValidForm::VFORM_STRING,
            'Required',
            ['required' => true]
        ));
        $page->addField($fieldset);

        $this->assertFalse($page->isValid());
    }

    #[Test]
    public function hasFieldsReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse((new Page('p1'))->hasFields());
    }

    #[Test]
    public function hasFieldsReturnsTrueWhenPopulated(): void
    {
        $page = new Page('p1');
        $page->addField(new Fieldset());

        $this->assertTrue($page->hasFields());
    }

    #[Test]
    public function isDynamicReturnsFalse(): void
    {
        $this->assertFalse((new Page('p1'))->isDynamic());
    }

    #[Test]
    public function getShortHeaderIgnoresSummaryLabelBecauseMetaIsNeverStored(): void
    {
        // KNOWN BUG: Page::__construct() reads class/style/overview from meta
        // manually but never calls `$this->__meta = $meta` (unlike Fieldset,
        // MultiField, Navigation, Note which all do). So getShortHeader()'s
        // `$this->getMeta("summaryLabel")` reads an empty array and falls back
        // to the full header regardless of what was passed in the constructor.
        //
        // When this is fixed (#204), flip this assertion to assertSame('Short', ...).
        $page = new Page('p1', 'Long Page Title', ['summaryLabel' => 'Short']);

        $this->assertSame('Long Page Title', $page->getShortHeader());
    }

    #[Test]
    public function getShortHeaderFallsBackToFullHeader(): void
    {
        $page = new Page('p1', 'Full Header');

        $this->assertSame('Full Header', $page->getShortHeader());
    }
}
