<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Textarea;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Textarea}.
 *
 * Textarea extends Element and renders `<textarea>`. It sets default
 * rows=5 and cols=21 in its constructor before delegating to Element.
 *
 * Security audit:
 * - Value is properly escaped with htmlspecialchars(ENT_QUOTES) ✓
 * - No new XSS vectors found.
 */
class TextareaTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['bio'], $_REQUEST['notes']);
    }

    #[Test]
    public function addFieldWithTextTypeReturnsTextareaInstance(): void
    {
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);

        $this->assertInstanceOf(Textarea::class, $field);
    }

    #[Test]
    public function toHtmlRendersTextareaElementWithNameAndId(): void
    {
        $field = $this->form->addField('bio', 'Biography', ValidForm::VFORM_TEXT);

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — the textarea element.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);
        $this->assertSame('bio', $textarea->getAttribute('name'));
        $this->assertSame('bio', $textarea->getAttribute('id'));
    }

    #[Test]
    public function toHtmlRendersDefaultRowsAndCols(): void
    {
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — default rows=5 cols=21 set by Textarea constructor.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);
        $this->assertSame('5', $textarea->getAttribute('rows'));
        $this->assertSame('21', $textarea->getAttribute('cols'));
    }

    #[Test]
    public function customRowsAndColsFromMetaAppendToDefaultsDueToSetMetaBehaviour(): void
    {
        // KNOWN LIMITATION: Textarea::__construct() sets default rows/cols via
        // setFieldMeta BEFORE calling parent::__construct(). The parent's
        // __initializeMeta() then processes meta['fieldrows'] and calls
        // setFieldMeta('rows', 10) — which APPENDS (not overwrites) because
        // $blnOverwrite defaults to false. Result: rows="5 10" instead of "10".
        //
        // This is a pre-existing meta-system limitation, not a rendering bug.
        // Browsers parse "5 10" as "5" (first valid int) so the custom value
        // is silently ignored.
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['fieldrows' => 10, 'fieldcols' => 80]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — the textarea element.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);

        // Documenting current (broken) behaviour: defaults get appended with custom values.
        $this->assertSame('5 10', $textarea->getAttribute('rows'));
        $this->assertSame('21 80', $textarea->getAttribute('cols'));
    }

    #[Test]
    public function toHtmlRendersSubmittedValueAsTextContent(): void
    {
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);
        $_REQUEST['bio'] = 'Hello world';

        $xpath = $this->parseHtml($field->toHtml(true));

        // `//textarea` — text content should be the submitted value.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);
        $this->assertSame('Hello world', trim($textarea->textContent));
    }

    #[Test]
    public function toHtmlRendersLabelLinkedToTextarea(): void
    {
        $field = $this->form->addField('bio', 'Your biography', ValidForm::VFORM_TEXT);

        $xpath = $this->parseHtml($field->toHtml());

        // `//label[@for="bio"]` — label linked by for attribute.
        $label = $xpath->query('//label[@for="bio"]')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Your biography', trim($label->textContent));
    }

    #[Test]
    public function toJsEmitsAddElementCall(): void
    {
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);

        $js = $field->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'bio'", $js);
    }

    #[Test]
    public function htmlTypeRendersAsTextarea(): void
    {
        // VFORM_HTML and VFORM_CUSTOM_TEXT also use Textarea as their concrete class.
        $field = $this->form->addField('notes', 'Notes', ValidForm::VFORM_HTML);

        $this->assertInstanceOf(Textarea::class, $field);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function valueIsHtmlEscapedInTextareaContent(): void
    {
        // SECURITY: textarea content must be escaped to prevent XSS. Unlike
        // input value attributes, textarea content is between tags, but
        // htmlspecialchars is still required to prevent tag injection.
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['default' => '</textarea><script>alert(1)</script>']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — the textarea element should exist (not broken by the payload).
        $textareas = $xpath->query('//textarea');
        $this->assertSame(1, $textareas->length);

        // `//script` — no injected script elements.
        $this->assertSame(0, $xpath->query('//script')->length);
    }
}
