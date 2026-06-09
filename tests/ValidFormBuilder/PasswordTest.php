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
 * - No new XSS vectors found. Label, tip, error and dynamicRemoveLabel
 *   strings render unescaped, but those are developer-supplied, not
 *   user input.
 * - KNOWN BUG: the simple-layout branch of __toHtml() emits a stray `"`
 *   after the wrapper div's meta string (`<div class="..."">`). Documented
 *   in simpleLayoutRendersHintAndMultifielditemClasses().
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
        unset(
            $_REQUEST['password'],
            $_REQUEST['password-confirm'],
            $_REQUEST['password_1'],
            $_REQUEST['password_dynamic']
        );
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

    #[Test]
    public function toHtmlRendersErrorParagraphWhenSubmittedInvalid(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['required' => true],
            ['required' => 'Password is required']
        );

        // Submitted without a value in $_REQUEST — required validation fails.
        $xpath = $this->parseHtml($field->toHtml(true));

        // `//p[@class="vf__error"]` — the error paragraph above the input.
        $error = $xpath->query('//p[@class="vf__error"]')->item(0);
        $this->assertNotNull($error);
        $this->assertSame('Password is required', trim($error->textContent));

        // `//div` — the wrapper carries the vf__error class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);
    }

    #[Test]
    public function toHtmlWithoutLabelAddsNolabelClassAndOmitsLabel(): void
    {
        $field = $this->form->addField('password', 'Password', ValidForm::VFORM_PASSWORD);

        $xpath = $this->parseHtml($field->toHtml(false, false, false));

        // `//label` — no label is rendered when $blnLabel is false.
        $this->assertSame(0, $xpath->query('//label')->length);

        // `//div` — the wrapper carries the vf__nolabel class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__nolabel', $classTokens);
    }

    #[Test]
    public function toHtmlRendersHintAsValueWithHintClass(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['hint' => 'Choose wisely']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="password"]` — unsubmitted fields render the hint as value.
        $input = $xpath->query('//input[@type="password"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('Choose wisely', $input->getAttribute('value'));

        // `//div` — the wrapper carries the vf__hint class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__hint', $classTokens);
    }

    #[Test]
    public function toHtmlSetsMaxlengthAttributeWhenConfigured(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['maxLength' => 64]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="password"]` — should carry a maxlength attribute.
        $input = $xpath->query('//input[@type="password"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('64', $input->getAttribute('maxlength'));
    }

    // --------------------------------------------------------------
    // Dynamic / removable fields
    // --------------------------------------------------------------

    #[Test]
    public function dynamicFieldRendersOriginalAndCloneMarkers(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another password']
        );
        $_REQUEST['password_dynamic'] = '1';

        $xpath = $this->parseHtml($field->toHtml());

        // `//div[@data-dynamic="original"]` — the first field is the original.
        $original = $xpath->query('//div[@data-dynamic="original"]')->item(0);
        $this->assertNotNull($original);

        // `//div[@data-dynamic="clone"]` — the second field is a clone with vf__clone.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);

        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);

        // `//input[@name="password_1"]` — the clone input gets the _1 suffix.
        $cloneInput = $xpath->query('//input[@name="password_1"]')->item(0);
        $this->assertNotNull($cloneInput);
        $this->assertSame('password_1', $cloneInput->getAttribute('id'));

        // `//div[@class="vf__dynamic"]/a` — the "add another" anchor after the last clone.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('Add another password', trim($anchor->textContent));
    }

    #[Test]
    public function removableFieldRendersRemoveLabelAnchor(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another', 'dynamicRemoveLabel' => 'Remove this password']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//div` — the wrapper carries the vf__removable class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);

        // `//a[contains(concat(" ", normalize-space(@class), " "), " vf__removeLabel ")]`
        // — the remove anchor inside the wrapper.
        $remove = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " vf__removeLabel ")]')->item(0);
        $this->assertNotNull($remove);
        $this->assertSame('Remove this password', trim($remove->textContent));
    }

    // --------------------------------------------------------------
    // Simple layout (MultiField item rendering)
    // --------------------------------------------------------------

    #[Test]
    public function simpleLayoutRendersHintAndMultifielditemClasses(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['hint' => 'Choose wisely']
        );

        $html = $field->__toHtml(false, true);

        // KNOWN BUG (documented, not fixed): Password's simple layout emits
        // `<div{$this->__getMetaString()}\">` — note the stray escaped quote —
        // producing malformed markup like `<div class="..."">`. Text and
        // Textarea do not have this typo. libxml recovers, so DOM assertions
        // below still work.
        $this->assertStringContainsString("\">\n", $html);

        $xpath = $this->parseHtml($html);

        // `//label` — simple layout never renders a label.
        $this->assertSame(0, $xpath->query('//label')->length);

        // `//div` — the wrapper carries both vf__hint and vf__multifielditem.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__hint', $classTokens);
        $this->assertContains('vf__multifielditem', $classTokens);
    }

    #[Test]
    public function simpleLayoutRendersErrorClassWithoutErrorParagraph(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['required' => true]
        );

        // Submitted without a value — validation fails.
        $xpath = $this->parseHtml($field->__toHtml(true, true));

        // `//div` — the wrapper carries the vf__error class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);

        // `//p` — simple layout signals errors via class only, no paragraph.
        $this->assertSame(0, $xpath->query('//p')->length);
    }

    #[Test]
    public function simpleLayoutRendersDynamicAndRemovableMarkers(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another', 'dynamicRemoveLabel' => 'Remove']
        );

        // Original (count 0) renders data-dynamic="original" plus vf__removable.
        $xpath = $this->parseHtml($field->__toHtml(false, true, true, true, 0));

        // `//div[@data-dynamic="original"]` — the original marker.
        $original = $xpath->query('//div[@data-dynamic="original"]')->item(0);
        $this->assertNotNull($original);

        $classTokens = preg_split('/\s+/', (string) $original->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);

        // Clone (count 1) renders data-dynamic="clone" plus vf__clone.
        $xpath = $this->parseHtml($field->__toHtml(false, true, true, true, 1));

        // `//div[@data-dynamic="clone"]` — the clone marker.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);

        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);

        // `//input[@name="password_1"]` — the clone input gets the _1 suffix.
        $this->assertSame(1, $xpath->query('//input[@name="password_1"]')->length);
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

    #[Test]
    public function toJsEncodesExternalJavascriptValidation(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['externalValidation' => ['javascript' => 'window.checkPassword']]
        );

        $js = $field->toJS();

        $this->assertStringContainsString('"window.checkPassword"', $js);
    }

    #[Test]
    public function toJsEmitsAddElementForEachDynamicField(): void
    {
        $field = $this->form->addField(
            'password',
            'Password',
            ValidForm::VFORM_PASSWORD,
            ['required' => true],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );
        $_REQUEST['password_dynamic'] = '1';

        $js = $field->toJS();

        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("objForm.addElement('password'", $js);
        $this->assertStringContainsString("objForm.addElement('password_1'", $js);

        // Dynamic fields are never required client-side once clones exist —
        // the required flag is forced to false for every occurrence.
        $this->assertMatchesRegularExpression("/addElement\('password_1', 'password_1', .+?, false, /", $js);
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
