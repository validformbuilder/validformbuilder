<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Text}.
 *
 * Text extends Element and renders `<input type="text">`. It's the
 * concrete class for VFORM_STRING, VFORM_EMAIL, VFORM_NUMERIC, etc.
 *
 * Security audit:
 * - Value is properly escaped with htmlspecialchars(ENT_QUOTES) ✓
 * - No new XSS vectors found.
 */
class TextTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['name'], $_REQUEST['email']);
    }

    #[Test]
    public function addFieldWithStringTypeReturnsTextInstance(): void
    {
        $field = $this->form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $this->assertInstanceOf(Text::class, $field);
    }

    #[Test]
    public function toHtmlRendersTextInputWithNameAndId(): void
    {
        $field = $this->form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — the text input element.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('name', $input->getAttribute('name'));
        $this->assertSame('name', $input->getAttribute('id'));
    }

    #[Test]
    public function toHtmlRendersLabelLinkedToInput(): void
    {
        $field = $this->form->addField('name', 'Full Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($field->toHtml());

        // `//label[@for="name"]` — label linked to the input.
        $label = $xpath->query('//label[@for="name"]')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Full Name', trim($label->textContent));
    }

    #[Test]
    public function toHtmlRendersSubmittedValueInInputAttribute(): void
    {
        $field = $this->form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $_REQUEST['name'] = 'Robin';

        $xpath = $this->parseHtml($field->toHtml(true));

        // `//input[@type="text"]` — value attribute should contain the submitted value.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('Robin', $input->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersDefaultValueWhenNotSubmitted(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['default' => 'Default Name']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — value should be the default.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('Default Name', $input->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersRequiredClassWhenRequired(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//div` — outer wrapper.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function toHtmlRendersTipWhenSet(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['tip' => 'Enter your full name']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]`
        // — the tip element.
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Enter your full name', trim($tip->textContent));
    }

    #[Test]
    public function toHtmlSetsMaxlengthAttributeWhenConfigured(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            ['maxLength' => 50]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — should have a maxlength attribute.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('50', $input->getAttribute('maxlength'));
    }

    #[Test]
    public function toJsEmitsAddElementCall(): void
    {
        $field = $this->form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $field->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'name'", $js);
    }

    #[Test]
    public function emailTypeRendersWithEmailCssClass(): void
    {
        $field = $this->form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $this->assertInstanceOf(Text::class, $field);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — email fields render as type="text" with a vf__email class.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);

        $classTokens = preg_split('/\s+/', (string) $input->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__email', $classTokens);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function valueIsHtmlEscapedInRenderedInput(): void
    {
        // SECURITY: the value attribute must be escaped via htmlspecialchars
        // to prevent XSS through submitted or default values.
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['default' => '"><img src=x onerror=alert(1)>']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — attack payload preserved as literal attribute value.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('"><img src=x onerror=alert(1)>', $input->getAttribute('value'));

        // `//img[@onerror]` — no injected element.
        $this->assertSame(0, $xpath->query('//img[@onerror]')->length);
    }
}
