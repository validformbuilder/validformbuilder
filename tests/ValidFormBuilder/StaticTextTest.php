<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Comparison;
use ValidFormBuilder\StaticText;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\StaticText}.
 *
 * StaticText renders a developer-provided HTML string into the form.
 * It extends Base and intentionally outputs the body as raw HTML.
 *
 * Security audit: raw HTML rendering is by design — the class is named
 * "StaticText" and documented as accepting "a simple string or even HTML
 * code". Developers must never pass user input. No new vulnerabilities.
 */
class StaticTextTest extends TestCase
{
    #[Test]
    public function constructorStoresBody(): void
    {
        $st = new StaticText('Hello world');

        $this->assertSame('Hello world', $st->getBody());
    }

    #[Test]
    public function toHtmlRendersBodyAsRawHtml(): void
    {
        $st = new StaticText('<span class="hint">Help text</span>');

        $this->assertStringContainsString('<span class="hint">Help text</span>', $st->toHtml());
    }

    #[Test]
    public function toHtmlReplacesMetaStringPlaceholder(): void
    {
        // StaticText replaces `[[metaString]]` in the body with the generated
        // HTML attributes from the meta array. This allows developers to inject
        // attributes into their custom HTML.
        $st = new StaticText('<div[[metaString]]>content</div>', ['id' => 'custom']);

        $html = $st->toHtml();

        $this->assertStringContainsString('id="custom"', $html);
        $this->assertStringNotContainsString('[[metaString]]', $html);
    }

    #[Test]
    public function toHtmlInSimpleLayoutWrapsBodyInDivSpan(): void
    {
        $st = new StaticText('Simple text');

        // Simple layout wraps the body in <div><span>body</span></div>.
        $html = $st->__toHtml(false, true);

        $this->assertStringContainsString('<span>Simple text</span>', $html);
    }

    #[Test]
    public function isValidAlwaysReturnsTrue(): void
    {
        $this->assertTrue((new StaticText('x'))->isValid());
    }

    #[Test]
    public function hasFieldsReturnsFalse(): void
    {
        $this->assertFalse((new StaticText('x'))->hasFields());
    }

    #[Test]
    public function getValidatorReturnsNull(): void
    {
        $this->assertNull((new StaticText('x'))->getValidator());
    }

    #[Test]
    public function isDynamicReturnsFalse(): void
    {
        $this->assertFalse((new StaticText('x'))->isDynamic());
    }

    #[Test]
    public function toJsReturnsEmptyStringWithoutIdMeta(): void
    {
        $this->assertSame('', (new StaticText('x'))->toJS());
    }

    #[Test]
    public function toJsEmitsAddElementCallWhenIdMetaIsSet(): void
    {
        $st = new StaticText('x', ['id' => 'static-1']);

        $this->assertSame("objForm.addElement('static-1', 'static-1');\n", $st->toJS());
    }

    #[Test]
    public function toJsAppendsConditionLogic(): void
    {
        $form = new ValidForm('static-text-js-form');
        $field = $form->addField('trigger-field', 'Trigger', ValidForm::VFORM_STRING);

        $st = new StaticText('Conditional text', ['id' => 'static-2']);
        $st->addCondition('visible', true, [
            new Comparison($field, ValidForm::VFORM_COMPARISON_EQUAL, 'sho"w</script>'),
        ]);

        $js = $st->toJS();

        $this->assertStringContainsString("objForm.addElement('static-2', 'static-2');", $js);
        $this->assertStringContainsString('objForm.addCondition(', $js);
        $this->assertStringContainsString('"subject":"static-2"', $js);

        // The condition payload goes through json_encode(), so a comparison
        // value containing quotes and tags is emitted JSON-escaped instead of
        // breaking out of the script context.
        $this->assertStringContainsString('"value":"sho\"w<\/script>"', $js);
        $this->assertStringNotContainsString('</script>', $js);
    }

    #[Test]
    public function getValueReturnsNull(): void
    {
        $this->assertNull((new StaticText('x'))->getValue());
    }

    #[Test]
    public function getNameReturnsNull(): void
    {
        $this->assertNull((new StaticText('x'))->getName());
    }

    #[Test]
    public function getDataReturnsNull(): void
    {
        $this->assertNull((new StaticText('x'))->getData('any-key'));
    }

    #[Test]
    public function rawHtmlRenderingIsIntentionalByDesign(): void
    {
        // Same contract as Note::toHtml() and Navigation::addHtml(): the class
        // is designed to inject developer-authored HTML into the form. Developers
        // must never pass user input to the constructor.
        $st = new StaticText('<script>alert(1)</script>');

        $this->assertStringContainsString('<script>', $st->toHtml());
    }
}
