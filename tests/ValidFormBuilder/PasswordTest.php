<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Password;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Password}.
 *
 * Password extends Element and renders `<input type="password">` with
 * `autocomplete="off"`. It shares most behaviour with Text but never
 * pre-fills the value on re-render for security reasons.
 *
 * Security audit:
 * - Value is properly escaped with htmlspecialchars(ENT_QUOTES) ✓
 * - Password type hides user input in the browser ✓
 * - autocomplete="off" prevents browser caching of passwords ✓
 * - No new XSS vectors found.
 */
class PasswordTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['password'], $_REQUEST['password-confirm']);
    }

    // --------------------------------------------------------------
    // Construction
    // --------------------------------------------------------------

    #[Test]
    public function addFieldWithPasswordTypeReturnsPasswordInstance(): void
    {
        $field = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);

        $this->assertInstanceOf(Password::class, $field);
        $this->assertSame(ValidForm::VFORM_PASSWORD, $field->getType());
    }

    // --------------------------------------------------------------
    // toHtml
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersPasswordInputWithAutocompleteOff(): void
    {
        $field = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="password"]` — the password input element.
        $input = $xpath->query('//input[@type="password"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('password', $input->getAttribute('name'));
        $this->assertSame('password', $input->getAttribute('id'));
        $this->assertSame('off', $input->getAttribute('autocomplete'));
    }

    #[Test]
    public function toHtmlRendersLabelLinkedToInput(): void
    {
        $field = $this->form->addField('password', 'Enter password', ValidForm::VFORM_PASSWORD);

        $xpath = $this->parseHtml($field->toHtml());

        // `//label[@for="password"]` — the label tied to the password input.
        $label = $xpath->query('//label[@for="password"]')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Enter password', trim($label->textContent));
    }

    #[Test]
    public function toHtmlRendersRequiredClassWhenRequired(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['required' => true]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//div` — the outer wrapper div.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function toHtmlRendersTipWhenSet(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['tip' => 'Must be 8+ characters']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]`
        // — the <small> tip element whose class list contains `vf__tip`.
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Must be 8+ characters', trim($tip->textContent));
    }

    // --------------------------------------------------------------
    // Validation
    // --------------------------------------------------------------

    #[Test]
    public function passwordFieldAcceptsAnyInputByDefault(): void
    {
        // VFORM_PASSWORD has an empty regex in Validator::$checks, so any input
        // passes the type check. Validation is left to minLength/maxLength/matchWith.
        $field = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);
        $_REQUEST['password'] = 'anything-goes!@#$%^&*()';

        $this->assertTrue($field->isValid());
    }

    #[Test]
    public function passwordFieldEnforcesMinLength(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['required' => true, 'minLength' => 8]
        );
        $_REQUEST['password'] = 'short';

        $this->assertFalse($field->isValid());
    }

    #[Test]
    public function passwordFieldEnforcesMatchWith(): void
    {
        $password = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);
        $confirm = $this->form->addField(
            'password-confirm',
            'Confirm',
            ValidForm::VFORM_PASSWORD,
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = 'secret123';
        $_REQUEST['password-confirm'] = 'different';

        $password->isValid();
        $this->assertFalse($confirm->isValid());
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsAddElementCallForPasswordField(): void
    {
        $field = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);

        $js = $field->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'password'", $js);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function valueIsHtmlEscapedInRenderedInput(): void
    {
        // SECURITY: even though passwords aren't typically re-rendered, the
        // value attribute must be escaped to prevent XSS if a default or
        // submitted value contains HTML-special characters.
        $field = new Password(
            'pw',
            ValidForm::VFORM_PASSWORD,
            'Password',
            [],
            [],
            ['default' => '"><script>alert(1)</script>']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="password"]` — the password input.
        $input = $xpath->query('//input[@type="password"]')->item(0);
        $this->assertNotNull($input);

        // The XSS payload is preserved as a literal attribute value, not injected HTML.
        $this->assertSame('"><script>alert(1)</script>', $input->getAttribute('value'));
        // `//script` — no injected script elements.
        $this->assertSame(0, $xpath->query('//script')->length);
    }
}
