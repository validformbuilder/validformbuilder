<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Button;
use ValidFormBuilder\Checkbox;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Comparison;
use ValidFormBuilder\Element;
use ValidFormBuilder\Fieldset;
use ValidFormBuilder\File;
use ValidFormBuilder\Group;
use ValidFormBuilder\Hidden;
use ValidFormBuilder\MultiField;
use ValidFormBuilder\Navigation;
use ValidFormBuilder\Paragraph;
use ValidFormBuilder\Password;
use ValidFormBuilder\Select;
use ValidFormBuilder\StaticText;
use ValidFormBuilder\Text;
use ValidFormBuilder\Textarea;
use ValidFormBuilder\ValidForm;
use Volnix\CSRF\CSRF;

/**
 * Coverage for {@link \ValidFormBuilder\ValidForm}.
 *
 * ValidForm is the central class of the library — it orchestrates form
 * construction, rendering, submission detection, and validation. This
 * test exercises the public API without re-testing the individual field
 * classes (those have their own dedicated tests).
 *
 * Surface covered:
 * - Constructor: name, description, action, uniqueId, elements collection.
 * - Factory methods: addField (each type → correct class), addFieldset,
 *   addHiddenField, addMultiField, addArea, addParagraph, addButton,
 *   addNavigation, addHtml.
 * - renderField(): static factory dispatch.
 * - isSubmitted(): dispatch-key detection.
 * - isValid(): full form validation pipeline.
 * - getFields(): flat field collection.
 * - getValidField(): field lookup by name.
 * - Static helpers: get(), getIsSet(), getStrippedClassName().
 * - setDefaults(): bulk default value assignment.
 * - toHtml(): full form rendering (structural).
 *
 * Security audit:
 * - CSRF protection integration via isSubmitted().
 * - Dispatch key check prevents cross-form submission spoofing.
 * - Form action defaults to $_SERVER['PHP_SELF'] (potential XSS if
 *   PHP_SELF is attacker-influenced, documented as known platform risk).
 */
class ValidFormTest extends TestCase
{
    use HtmlAssertionsTrait;

    protected function tearDown(): void
    {
        foreach (array_keys($_REQUEST) as $key) {
            unset($_REQUEST[$key]);
        }

        foreach (array_keys($_POST) as $key) {
            unset($_POST[$key]);
        }

        unset($_SERVER['REQUEST_URI']);
        unset($_SESSION[CSRF::TOKEN_NAME]);
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresNameAndDescription(): void
    {
        $form = new ValidForm('contact', 'Contact us');

        $this->assertSame('contact', $form->getName());
        $this->assertSame('Contact us', $form->getDescription());
    }

    #[Test]
    public function constructorSetsExplicitAction(): void
    {
        $form = new ValidForm('test', null, '/submit');

        $this->assertSame('/submit', $form->getAction());
    }

    #[Test]
    public function constructorInitialisesEmptyElementsCollection(): void
    {
        $form = new ValidForm('test');

        $this->assertInstanceOf(Collection::class, $form->getFields());
    }

    #[Test]
    public function constructorGeneratesUniqueId(): void
    {
        $form = new ValidForm('test');

        $this->assertNotEmpty($form->getUniqueId());
        $this->assertSame(8, strlen($form->getUniqueId()));
    }

    #[Test]
    public function constructorAcceptsCustomUniqueIdViaMeta(): void
    {
        $form = new ValidForm('test', null, null, ['uniqueId' => 'custom123']);

        $this->assertSame('custom123', $form->getUniqueId());
    }

    // --------------------------------------------------------------
    // Factory methods — addField for each type
    // --------------------------------------------------------------

    #[Test]
    #[DataProvider('fieldTypeToClassProvider')]
    public function addFieldReturnsCorrectClassForEachType(int $type, string $expectedClass): void
    {
        $form = new ValidForm('test');
        $field = $form->addField('field', 'Field', $type);

        $this->assertInstanceOf($expectedClass, $field);
    }

    public static function fieldTypeToClassProvider(): array
    {
        return [
            'string'      => [ValidForm::VFORM_STRING, Text::class],
            'text'        => [ValidForm::VFORM_TEXT, Textarea::class],
            'numeric'     => [ValidForm::VFORM_NUMERIC, Text::class],
            'integer'     => [ValidForm::VFORM_INTEGER, Text::class],
            'word'        => [ValidForm::VFORM_WORD, Text::class],
            'email'       => [ValidForm::VFORM_EMAIL, Text::class],
            'password'    => [ValidForm::VFORM_PASSWORD, Password::class],
            'url'         => [ValidForm::VFORM_URL, Text::class],
            'simpleurl'   => [ValidForm::VFORM_SIMPLEURL, Text::class],
            'file'        => [ValidForm::VFORM_FILE, File::class],
            'boolean'     => [ValidForm::VFORM_BOOLEAN, Checkbox::class],
            'radio_list'  => [ValidForm::VFORM_RADIO_LIST, Group::class],
            'check_list'  => [ValidForm::VFORM_CHECK_LIST, Group::class],
            'select_list' => [ValidForm::VFORM_SELECT_LIST, Select::class],
            'currency'    => [ValidForm::VFORM_CURRENCY, Text::class],
            'date'        => [ValidForm::VFORM_DATE, Text::class],
            'custom'      => [ValidForm::VFORM_CUSTOM, Text::class],
            'custom_text' => [ValidForm::VFORM_CUSTOM_TEXT, Textarea::class],
            'html'        => [ValidForm::VFORM_HTML, Textarea::class],
            'hidden'      => [ValidForm::VFORM_HIDDEN, Hidden::class],
        ];
    }

    // --------------------------------------------------------------
    // Other factory methods
    // --------------------------------------------------------------

    #[Test]
    public function addFieldsetReturnsFieldsetInstance(): void
    {
        $form = new ValidForm('test');
        $fieldset = $form->addFieldset('Section');

        $this->assertInstanceOf(Fieldset::class, $fieldset);
    }

    #[Test]
    public function addHiddenFieldReturnsHiddenInstance(): void
    {
        $form = new ValidForm('test');
        $hidden = $form->addHiddenField('secret', ValidForm::VFORM_STRING);

        $this->assertInstanceOf(Hidden::class, $hidden);
    }

    #[Test]
    public function addMultiFieldReturnsMultiFieldInstance(): void
    {
        $form = new ValidForm('test');
        $multi = $form->addMultiField('Full name');

        $this->assertInstanceOf(MultiField::class, $multi);
    }

    #[Test]
    public function addAreaReturnsAreaInstance(): void
    {
        $form = new ValidForm('test');
        $area = $form->addArea('Address', true);

        $this->assertInstanceOf(Area::class, $area);
    }

    #[Test]
    public function addParagraphReturnsParagraphInstance(): void
    {
        $form = new ValidForm('test');
        $p = $form->addParagraph('Help text', 'Note');

        $this->assertInstanceOf(Paragraph::class, $p);
    }

    #[Test]
    public function addButtonReturnsButtonInstance(): void
    {
        $form = new ValidForm('test');
        $btn = $form->addButton('Click me');

        $this->assertInstanceOf(Button::class, $btn);
    }

    #[Test]
    public function addNavigationReturnsNavigationInstance(): void
    {
        $form = new ValidForm('test');
        $nav = $form->addNavigation();

        $this->assertInstanceOf(Navigation::class, $nav);
    }

    #[Test]
    public function addHtmlReturnsStaticTextInstance(): void
    {
        $form = new ValidForm('test');
        $html = $form->addHtml('<p>Custom content</p>');

        $this->assertInstanceOf(StaticText::class, $html);
    }

    // --------------------------------------------------------------
    // isSubmitted
    // --------------------------------------------------------------

    #[Test]
    public function isSubmittedReturnsFalseByDefault(): void
    {
        $form = new ValidForm('contact');

        $this->assertFalse($form->isSubmitted());
    }

    #[Test]
    public function isSubmittedReturnsTrueWhenDispatchKeyMatches(): void
    {
        // ValidForm checks $_REQUEST['vf__dispatch'] == form name. CSRF
        // protection is enabled by default and would reject the request
        // in a test environment (no valid token), so disable it for this test.
        $form = new ValidForm('contact');
        $form->setUseCsrfProtection(false);
        $_REQUEST['vf__dispatch'] = 'contact';

        $this->assertTrue($form->isSubmitted());
    }

    #[Test]
    public function isSubmittedReturnsFalseWhenDispatchKeyDoesNotMatch(): void
    {
        $form = new ValidForm('contact');
        $_REQUEST['vf__dispatch'] = 'different-form';

        $this->assertFalse($form->isSubmitted());
    }

    #[Test]
    public function isSubmittedCanBeForced(): void
    {
        $form = new ValidForm('contact');

        // $blnForce = true bypasses the dispatch check.
        $this->assertTrue($form->isSubmitted(true));
    }

    // --------------------------------------------------------------
    // isValid
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueForEmptyForm(): void
    {
        $form = new ValidForm('test');

        $this->assertTrue($form->isValid());
    }

    #[Test]
    public function isValidReturnsTrueWhenAllFieldsPass(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        // No required fields, no input → passes.
        $this->assertTrue($form->isValid());
    }

    #[Test]
    public function isValidReturnsFalseWhenARequiredFieldIsEmpty(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING, ['required' => true]);

        $this->assertFalse($form->isValid());
    }

    #[Test]
    public function isValidReturnsTrueWhenRequiredFieldHasValue(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING, ['required' => true]);
        $_REQUEST['name'] = 'Robin';

        $this->assertTrue($form->isValid());
    }

    // --------------------------------------------------------------
    // getFields / getValidField
    // --------------------------------------------------------------

    #[Test]
    public function getFieldsReturnsFlatCollectionOfAllFields(): void
    {
        $form = new ValidForm('test');
        $form->addField('first', 'First', ValidForm::VFORM_STRING);
        $form->addField('last', 'Last', ValidForm::VFORM_STRING);

        $fields = $form->getFields();

        $this->assertInstanceOf(Collection::class, $fields);
        // getFields returns all fields including any internal dispatch/hidden
        // fields the form creates. At minimum our two fields are in there.
        $this->assertGreaterThanOrEqual(2, $fields->count());
    }

    #[Test]
    public function getValidFieldReturnsFieldByName(): void
    {
        $form = new ValidForm('test');
        $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $field = $form->getValidField('email');

        $this->assertNotNull($field);
        $this->assertSame('email', $field->getName());
    }

    #[Test]
    public function getValidFieldReturnsNullForUnknownName(): void
    {
        $form = new ValidForm('test');

        $this->assertNull($form->getValidField('nonexistent'));
    }

    // --------------------------------------------------------------
    // setDefaults
    // --------------------------------------------------------------

    #[Test]
    public function setDefaultsSetsDefaultValueAppliedDuringRendering(): void
    {
        // setDefaults() stores values on the form, not directly on the fields.
        // They are applied during fieldsToHtml() / toHtml(). We verify the
        // rendered output contains the default value.
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $form->setDefaults(['name' => 'Default Name']);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//input[@name="name"]` — the text input for the 'name' field.
        $input = $xpath->query('//input[@name="name"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('Default Name', $input->getAttribute('value'));
    }

    // --------------------------------------------------------------
    // Static helpers
    // --------------------------------------------------------------

    #[Test]
    public function getReturnsRequestValue(): void
    {
        $_REQUEST['key'] = 'value';

        $this->assertSame('value', ValidForm::get('key'));
    }

    #[Test]
    public function getReturnsReplacementForMissingKey(): void
    {
        $this->assertSame('fallback', ValidForm::get('missing', 'fallback'));
    }

    #[Test]
    public function getIsSetReturnsTrueForExistingKey(): void
    {
        $_REQUEST['exists'] = 'yes';

        $this->assertTrue(ValidForm::getIsSet('exists'));
    }

    #[Test]
    public function getIsSetReturnsFalseForMissingKey(): void
    {
        $this->assertFalse(ValidForm::getIsSet('missing'));
    }

    #[Test]
    public function getStrippedClassNameRemovesNamespace(): void
    {
        $this->assertSame('ValidForm', ValidForm::getStrippedClassName('ValidFormBuilder\\ValidForm'));
        $this->assertSame('Text', ValidForm::getStrippedClassName('ValidFormBuilder\\Text'));
    }

    // --------------------------------------------------------------
    // toHtml — structural rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersFormElement(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('contact', 'Contact form');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//form` — the form element.
        $formEl = $xpath->query('//form')->item(0);
        $this->assertNotNull($formEl);
        $this->assertSame('contact', $formEl->getAttribute('id'));
    }

    #[Test]
    public function toHtmlIncludesHiddenDispatchField(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('contact');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//input[@type="hidden" and @name="vf__dispatch"]` — the dispatch
        // field that isSubmitted() checks to determine if this form was submitted.
        $dispatch = $xpath->query('//input[@type="hidden" and @name="vf__dispatch"]')->item(0);
        $this->assertNotNull($dispatch);
        $this->assertSame('contact', $dispatch->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersChildFieldsInsideForm(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('contact');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//form//input[@type="text"]` — text inputs rendered inside the form.
        $inputs = $xpath->query('//form//input[@type="text"]');
        $this->assertGreaterThanOrEqual(2, $inputs->length);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function isSubmittedRejectsWrongDispatchKey(): void
    {
        // SECURITY: the dispatch key prevents one form from accidentally
        // processing a submission intended for a different form. An attacker
        // submitting vf__dispatch=wrong-form should not trigger validation
        // on the target form.
        $form = new ValidForm('real-form');
        $_REQUEST['vf__dispatch'] = 'attacker-form';

        $this->assertFalse($form->isSubmitted());
    }

    #[Test]
    public function getReturnsSanitizedEmptyForMissingKeyByDefault(): void
    {
        // SECURITY: ValidForm::get() returns an empty string (not null or
        // undefined) for missing keys, preventing null-reference bugs in
        // downstream code that doesn't check for nullability.
        $this->assertSame('', ValidForm::get('nonexistent'));
    }

    #[Test]
    public function dispatchFieldContainsFormNameInValueAttribute(): void
    {
        // The dispatch hidden field's value is the form name. Since the name
        // is developer-provided (not user input), the library renders it
        // as-is. This test verifies the dispatch field is present and carries
        // the form name for a well-formed form name.
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('my-form');
        $form->addField('x', 'X', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//input[@name="vf__dispatch"]` — the dispatch hidden input.
        $dispatch = $xpath->query('//input[@name="vf__dispatch"]')->item(0);
        $this->assertNotNull($dispatch);
        $this->assertSame('my-form', $dispatch->getAttribute('value'));
    }

    // --------------------------------------------------------------
    // getLastFieldset
    // --------------------------------------------------------------

    #[Test]
    public function getLastFieldsetReturnsExistingFieldset(): void
    {
        $form = new ValidForm('test');
        $fieldset = $form->addFieldset('Contact');
        $form->addFieldset('Extra');

        // getLastFieldset returns the most recently added fieldset.
        $last = $form->getLastFieldset();
        $this->assertInstanceOf(Fieldset::class, $last);
    }

    #[Test]
    public function getLastFieldsetCreatesOneWhenFormIsEmpty(): void
    {
        $form = new ValidForm('test');

        // No fieldsets added — getLastFieldset should auto-create one.
        $fieldset = $form->getLastFieldset();
        $this->assertInstanceOf(Fieldset::class, $fieldset);
    }

    // --------------------------------------------------------------
    // setAutoComplete
    // --------------------------------------------------------------

    #[Test]
    public function setAutoCompleteToggle(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test');
        $form->setAutoComplete(false);
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//form` — check autocomplete attribute is set to "off".
        $formEl = $xpath->query('//form')->item(0);
        $this->assertNotNull($formEl);
        $this->assertSame('off', $formEl->getAttribute('autocomplete'));
    }

    // --------------------------------------------------------------
    // generateId
    // --------------------------------------------------------------

    #[Test]
    public function generateIdReturnsStringOfRequestedLength(): void
    {
        $form = new ValidForm('test');

        $this->assertSame(8, strlen($form->generateId()));
        $this->assertSame(16, strlen($form->generateId(16)));
    }

    #[Test]
    public function generateIdReturnsAlphanumericCharacters(): void
    {
        $form = new ValidForm('test');

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $form->generateId(32));
    }

    // --------------------------------------------------------------
    // getInvalidFields
    // --------------------------------------------------------------

    #[Test]
    public function getInvalidFieldsReturnsEmptyArrayWhenAllValid(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $this->assertSame([], $form->getInvalidFields());
    }

    #[Test]
    public function getInvalidFieldsReturnsFieldNamesWithErrors(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING, ['required' => true]);

        $invalid = $form->getInvalidFields();

        $this->assertNotEmpty($invalid);
        $this->assertArrayHasKey('name', $invalid[0]);
    }

    // --------------------------------------------------------------
    // addJSEvent
    // --------------------------------------------------------------

    #[Test]
    public function addJSEventRegistersEventCallback(): void
    {
        $form = new ValidForm('test');
        $form->addJSEvent('beforeSubmit', 'myCallback');

        // JS events are rendered in the JS output; verify the callback
        // appears in the generated javascript.
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->toJs();
        $this->assertStringContainsString('myCallback', $js);
        $this->assertStringContainsString('beforeSubmit', $js);
    }

    // --------------------------------------------------------------
    // elementsToJs
    // --------------------------------------------------------------

    #[Test]
    public function elementsToJsReturnsScriptBlockByDefault(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->elementsToJs();

        $this->assertStringContainsString('<script', $js);
        $this->assertStringContainsString('objForm.addElement', $js);
        $this->assertStringContainsString('objForm.initialize()', $js);
    }

    #[Test]
    public function elementsToJsRawOmitsScriptTags(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->elementsToJs(true);

        $this->assertStringNotContainsString('<script', $js);
        $this->assertStringContainsString('objForm.addElement', $js);
    }

    // --------------------------------------------------------------
    // toJs
    // --------------------------------------------------------------

    #[Test]
    public function toJsGeneratesRawJavascriptOutput(): void
    {
        $form = new ValidForm('test');
        $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $js = $form->toJs();

        $this->assertStringContainsString('objForm', $js);
    }

    // --------------------------------------------------------------
    // serialize / unserialize
    // --------------------------------------------------------------

    #[Test]
    public function serializeAndUnserializeRoundTrip(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $serialized = $form->serialize(false);

        // Serialized output is a non-empty base64 string.
        $this->assertNotEmpty($serialized);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $serialized);

        // Round-trip: unserialize should produce a ValidForm instance.
        $restored = ValidForm::unserialize($serialized);
        $this->assertInstanceOf(ValidForm::class, $restored);
        $this->assertSame('test', $restored->getName());
    }

    // --------------------------------------------------------------
    // getCachedFields
    // --------------------------------------------------------------

    #[Test]
    public function getCachedFieldsReturnsFieldCollectionWithoutPriorCache(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $cached = $form->getCachedFields();

        $this->assertInstanceOf(Collection::class, $cached);
        $this->assertGreaterThan(0, $cached->count());
    }

    // --------------------------------------------------------------
    // valuesAsHtml
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlRendersTableWithSubmittedValues(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $_REQUEST['name'] = 'Robin';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $xpath = $this->parseHtml($html);

        // `//table` — the values overview table.
        $table = $xpath->query('//table')->item(0);
        $this->assertNotNull($table);
    }

    #[Test]
    public function valuesAsHtmlReturnsEmptyStringWhenNoValues(): void
    {
        $form = new ValidForm('test');

        $html = $form->valuesAsHtml();

        $this->assertSame('', $html);
    }

    // --------------------------------------------------------------
    // fieldsToHtml
    // --------------------------------------------------------------

    #[Test]
    public function fieldsToHtmlRendersAllFields(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        $html = $form->fieldsToHtml();

        $xpath = $this->parseHtml($html);

        // `//input[@name="name"]` — the name field should be rendered.
        $this->assertSame(1, $xpath->query('//input[@name="name"]')->length);
        // `//input[@name="email"]` — the email field should be rendered.
        $this->assertSame(1, $xpath->query('//input[@name="email"]')->length);
    }

    // --------------------------------------------------------------
    // Constructor — action derived from REQUEST_URI
    // --------------------------------------------------------------

    #[Test]
    public function constructorDerivesActionFromRequestUriPath(): void
    {
        // When no action is given and REQUEST_URI is available, the action
        // is the *path* component of REQUEST_URI (query string stripped).
        $_SERVER['PHP_SELF'] = '/fallback.php';
        $_SERVER['REQUEST_URI'] = '/contact/form?utm_source=test';

        $form = new ValidForm('test');

        $this->assertSame('/contact/form', $form->getAction());
    }

    // --------------------------------------------------------------
    // setDefaults — invalid argument
    // --------------------------------------------------------------

    #[Test]
    public function setDefaultsThrowsInvalidArgumentExceptionForNonArray(): void
    {
        $form = new ValidForm('test');

        $this->expectException(\InvalidArgumentException::class);

        $form->setDefaults('not-an-array');
    }

    // --------------------------------------------------------------
    // renderField — unknown type fallback
    // --------------------------------------------------------------

    #[Test]
    public function renderFieldFallsBackToGenericElementForUnknownType(): void
    {
        // An unrecognized type constant falls through to the default case
        // and yields a bare Element instance.
        $field = ValidForm::renderField('mystery', 'Mystery', 999, [], [], []);

        $this->assertSame(Element::class, get_class($field));
    }

    // --------------------------------------------------------------
    // toHtml — client-side javascript, meta class, main alert, data attrs
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlIncludesClientSideJavascriptByDefault(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('js-form');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $html = $form->toHtml();

        // The default $blnClientSide = true prepends a <script> block with
        // the form's init function before the <form> element.
        $this->assertStringContainsString('<script type="text/javascript">', $html);
        $this->assertStringContainsString('function js_form_init() {', $html);
        $this->assertStringContainsString('// ]]>', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    #[Test]
    public function toHtmlAppendsCustomClassFromMeta(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test', null, null, ['class' => 'my-custom-class']);
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//form` — the meta class is appended to the default 'validform' class.
        $formEl = $xpath->query('//form')->item(0);
        $this->assertNotNull($formEl);
        $this->assertSame('validform my-custom-class', $formEl->getAttribute('class'));
    }

    #[Test]
    public function toHtmlRendersMainAlertWhenForceSubmitted(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test');
        $form->setMainAlert('Something went wrong');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false, true));

        // `//div[@class="vf__main_error"]/p` — the main alert paragraph.
        $alert = $xpath->query('//div[@class="vf__main_error"]/p')->item(0);
        $this->assertNotNull($alert);
        $this->assertSame('Something went wrong', $alert->textContent);
    }

    #[Test]
    public function toHtmlRendersDataAttributesFromMeta(): void
    {
        $_SERVER['PHP_SELF'] = '/test.php';
        $form = new ValidForm('test', null, null, ['data' => ['FooBar' => 'baz', 'other' => 'qux']]);
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($form->toHtml(false));

        // `//form` — data keys are lowercased into data-* attributes.
        $formEl = $xpath->query('//form')->item(0);
        $this->assertNotNull($formEl);
        $this->assertSame('baz', $formEl->getAttribute('data-foobar'));
        $this->assertSame('qux', $formEl->getAttribute('data-other'));
    }

    // --------------------------------------------------------------
    // fieldsToHtml — dynamic defaults and navigation flag
    // --------------------------------------------------------------

    #[Test]
    public function fieldsToHtmlDerivesDynamicCounterDefaultFromArrayDefault(): void
    {
        // When an array of defaults is set on a dynamic field and no
        // explicit '<name>_dynamic' counter default is given, the counter
        // default is derived from the array length (zero-based).
        $form = new ValidForm('test');
        $form->addField('colors', 'Colors', ValidForm::VFORM_STRING, [], [], [
            'dynamic' => true,
            'dynamicLabel' => 'Add another color',
        ]);
        $form->setDefaults(['colors' => ['red', 'blue']]);

        $html = $form->fieldsToHtml();

        $this->assertSame(1, $form->getDefaults()['colors_dynamic']);

        $xpath = $this->parseHtml($html);

        // `//input[@name="colors_1"]` — the dynamic duplicate is rendered
        // because the derived counter default is 1.
        $duplicate = $xpath->query('//input[@name="colors_1"]')->item(0);
        $this->assertNotNull($duplicate);
        $this->assertSame('blue', $duplicate->getAttribute('value'));
    }

    #[Test]
    public function fieldsToHtmlReportsNavigationThroughReferenceParameter(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $form->addNavigation();

        $blnNavigation = false;
        $form->fieldsToHtml(false, $blnNavigation);

        // The by-reference flag tells toHtml() not to render the default
        // submit button navigation block.
        $this->assertTrue($blnNavigation);
    }

    // --------------------------------------------------------------
    // isSubmitted — CSRF token validation
    // --------------------------------------------------------------

    #[Test]
    public function isSubmittedReturnsFalseWithoutValidCsrfToken(): void
    {
        // SECURITY: with CSRF protection enabled (the default), a matching
        // dispatch key alone is not enough — the request must also carry a
        // valid CSRF token in $_POST. Without one, isSubmitted() is false.
        $form = new ValidForm('csrf-form');
        $_REQUEST['vf__dispatch'] = 'csrf-form';

        $this->assertFalse($form->isSubmitted());
    }

    #[Test]
    public function isSubmittedReturnsTrueWithValidCsrfToken(): void
    {
        // SECURITY: the happy path — the session token matches the posted
        // token, so the submission is accepted.
        $form = new ValidForm('csrf-form');
        $_REQUEST['vf__dispatch'] = 'csrf-form';
        $_POST[CSRF::TOKEN_NAME] = CSRF::getToken();

        $this->assertTrue($form->isSubmitted());
    }

    // --------------------------------------------------------------
    // getFields — nested containers and empty fieldsets
    // --------------------------------------------------------------

    #[Test]
    public function getFieldsCollectsActiveAreasAndNestedContainers(): void
    {
        $form = new ValidForm('test');

        // Active area directly inside the fieldset; the area itself is
        // added to the flat collection because it's active.
        $outerArea = $form->addArea('Outer', true, 'outer');
        $outerArea->addField('outer-text', 'Outer text', ValidForm::VFORM_STRING);

        // A multifield inside the area: its children are collected too.
        $multi = $outerArea->addMultiField('Inner multifield');
        $multi->addField('multi-sub', ValidForm::VFORM_STRING);

        // An active area nested inside another area. This isn't reachable
        // through the factory methods, but getFields() supports it for
        // subclasses and manual collection manipulation.
        $innerArea = new Area('Inner', true, 'inner');
        $innerArea->addField('deep-field', 'Deep', ValidForm::VFORM_STRING);
        $outerArea->getFields()->addObject($innerArea);

        $fields = $form->getFields();

        $names = [];
        foreach ($fields as $field) {
            $names[] = $field->getName();
        }

        $this->assertContains('outer', $names);
        $this->assertContains('outer-text', $names);
        $this->assertContains('multi-sub', $names);
        $this->assertContains('inner', $names);
        $this->assertContains('deep-field', $names);
    }

    #[Test]
    public function getFieldsAddsFieldlessTopLevelElementToCollection(): void
    {
        // Top-level elements without child fields (like StaticText added
        // through addHtml) are added to the flat collection as-is.
        $form = new ValidForm('test');
        $html = $form->addHtml('<p>Static content</p>');

        $fields = $form->getFields();

        $this->assertSame(1, $fields->count());

        foreach ($fields as $field) {
            $this->assertSame($html, $field);
        }
    }

    // --------------------------------------------------------------
    // getValidField — lookup by name fallback
    // --------------------------------------------------------------

    #[Test]
    public function getValidFieldFallsBackToNameLookupForChecklistFields(): void
    {
        // A checklist named with [] gets a randomized internal id, so the
        // first lookup pass (by id) fails and the second pass matches the
        // field by its name instead.
        $form = new ValidForm('test');
        $field = $form->addField('interests[]', 'Interests', ValidForm::VFORM_CHECK_LIST);

        $this->assertSame($field, $form->getValidField('interests[]'));
    }

    // --------------------------------------------------------------
    // valuesAsHtml — no-values message and conditions
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlShowsNoValuesMessageWhenFormIsEmpty(): void
    {
        $form = new ValidForm('test');
        $form->setNoValuesMessage('Nothing was submitted');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('Nothing was submitted', $html);
    }

    #[Test]
    public function valuesAsHtmlSkipsFieldsetHiddenByVisibleCondition(): void
    {
        // The trigger field lives in the first fieldset; the second fieldset
        // is only visible when trigger equals 'show'. The trigger is not
        // submitted, so the condition is not met and the fieldset (and its
        // submitted child value) is omitted from the overview.
        $form = new ValidForm('test');
        $trigger = $form->addField('trigger', 'Trigger', ValidForm::VFORM_STRING);

        $fieldset = $form->addFieldset('Hidden section');
        $fieldset->addCondition('visible', true, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'show'),
        ]);
        $form->addField('secret', 'Secret', ValidForm::VFORM_STRING);

        $_REQUEST['secret'] = 'classified';
        $form->isValid();

        $this->assertStringNotContainsString('classified', $form->valuesAsHtml());
    }

    #[Test]
    public function valuesAsHtmlSkipsFieldHiddenByMetVisibleCondition(): void
    {
        // visible=false when trigger equals 'hide'; the trigger IS submitted
        // with 'hide', so the condition is met and the field is hidden.
        $form = new ValidForm('test');
        $trigger = $form->addField('trigger', 'Trigger', ValidForm::VFORM_STRING);
        $target = $form->addField('target', 'Target', ValidForm::VFORM_STRING);
        $target->addCondition('visible', false, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'hide'),
        ]);

        $_REQUEST['trigger'] = 'hide';
        $_REQUEST['target'] = 'invisible-value';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('hide', $html);
        $this->assertStringNotContainsString('invisible-value', $html);
    }

    #[Test]
    public function valuesAsHtmlSkipsMultiFieldHiddenByVisibleCondition(): void
    {
        $form = new ValidForm('test');
        $trigger = $form->addField('trigger', 'Trigger', ValidForm::VFORM_STRING);

        $multi = $form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addCondition('visible', true, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'show'),
        ]);

        $_REQUEST['first-name'] = 'Robin';
        $form->isValid();

        $this->assertStringNotContainsString('Robin', $form->valuesAsHtml());
    }

    // --------------------------------------------------------------
    // valuesAsHtml — field rendering variations
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlRendersFieldsetHeaderRow(): void
    {
        $form = new ValidForm('test');
        $form->addFieldset('Personal details');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $_REQUEST['name'] = 'Robin';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<b>Personal details</b>', $html);
        $this->assertStringContainsString('Robin', $html);
    }

    #[Test]
    public function valuesAsHtmlImplodesChecklistValues(): void
    {
        $form = new ValidForm('test');
        $checklist = $form->addField('interests[]', 'Interests', ValidForm::VFORM_CHECK_LIST);
        $checklist->addField('Option 1', 'opt1');
        $checklist->addField('Option 2', 'opt2');

        $_REQUEST['interests'] = ['opt1', 'opt2'];
        $form->isValid();

        $html = $form->valuesAsHtml();

        // Array values are joined with ', ' in the overview.
        $this->assertStringContainsString('opt1, opt2', $html);
    }

    #[Test]
    public function valuesAsHtmlRendersBooleanFieldAsYes(): void
    {
        $form = new ValidForm('test');
        $form->addField('agree', 'Agree to terms', ValidForm::VFORM_BOOLEAN);

        $_REQUEST['agree'] = 'on';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('Agree to terms', $html);
        $this->assertStringContainsString('<strong>yes</strong>', $html);
    }

    #[Test]
    public function valuesAsHtmlRendersDynamicFieldDuplicates(): void
    {
        // Dynamic fields get _1, _2 etc. suffixed clones client-side. The
        // overview renders the base value plus every dynamic duplicate.
        $form = new ValidForm('test');
        $form->addField('phone', 'Phone', ValidForm::VFORM_STRING, [], [], [
            'dynamic' => true,
            'dynamicLabel' => 'Add phone number',
        ]);

        $_REQUEST['phone'] = '555-0001';
        $_REQUEST['phone_1'] = '555-0002';
        $_REQUEST['phone_dynamic'] = '1';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('555-0001', $html);
        $this->assertStringContainsString('555-0002', $html);
    }

    // --------------------------------------------------------------
    // valuesAsHtml — areas
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlRendersAreaWithHeaderAndChildValues(): void
    {
        $form = new ValidForm('test');
        $area = $form->addArea('Shipping address', false, 'shipping');
        $area->addField('city', 'City', ValidForm::VFORM_STRING);
        $area->addParagraph('Paragraphs are skipped in the overview.');

        $_REQUEST['city'] = 'Amsterdam';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<h3>Shipping address</h3>', $html);
        $this->assertStringContainsString('Amsterdam', $html);
        $this->assertStringNotContainsString('Paragraphs are skipped', $html);
    }

    #[Test]
    public function valuesAsHtmlRendersMultiFieldNestedInsideArea(): void
    {
        $form = new ValidForm('test');
        $area = $form->addArea('Personal', false, 'personal');
        $multi = $area->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $_REQUEST['first-name'] = 'Robin';
        $_REQUEST['last-name'] = 'van Baalen';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<h3>Personal</h3>', $html);
        $this->assertStringContainsString('<strong>Robin van Baalen</strong>', $html);
    }

    #[Test]
    public function valuesAsHtmlShowsNoValuesMessageForEmptyActiveArea(): void
    {
        // An active (checked) area without any submitted child values shows
        // the no-values message under the area header.
        $form = new ValidForm('test');
        $form->setNoValuesMessage('No values entered');
        $area = $form->addArea('Optional info', true, 'optional');
        $area->addField('comment', 'Comment', ValidForm::VFORM_STRING);

        $_REQUEST['optional'] = '1';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<h3>Optional info</h3>', $html);
        $this->assertStringContainsString('No values entered', $html);
    }

    #[Test]
    public function valuesAsHtmlReturnsEmptyStringForAreaWithoutContent(): void
    {
        // A non-active area without submitted child values and without a
        // no-values message produces no overview output at all.
        $form = new ValidForm('test');
        $area = $form->addArea('Empty area', false, 'empty-area');
        $area->addField('city', 'City', ValidForm::VFORM_STRING);

        $form->isValid();

        $this->assertSame('', $form->valuesAsHtml());
    }

    #[Test]
    public function valuesAsHtmlRendersDynamicAreaDuplicates(): void
    {
        // A dynamic area renders one block per dynamic count. The hidden
        // dynamic counter fields inside the area are skipped.
        $form = new ValidForm('test');
        $area = $form->addArea('Addresses', false, 'addresses', false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add address',
        ]);
        $area->addField('street', 'Street', ValidForm::VFORM_STRING);

        $_REQUEST['street'] = 'Main Street 1';
        $_REQUEST['street_1'] = 'Second Street 2';
        $_REQUEST['street_dynamic'] = '1';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('Main Street 1', $html);
        $this->assertStringContainsString('Second Street 2', $html);
        // The dynamic counter value must not leak into the overview.
        $this->assertStringNotContainsString('street_dynamic', $html);
    }

    #[Test]
    public function valuesAsHtmlRendersNestedDynamicFieldInsideArea(): void
    {
        // A static area containing a dynamic child field renders the base
        // value plus all dynamic duplicates of that child.
        $form = new ValidForm('test');
        $area = $form->addArea('Contact', false, 'contact-area');
        $area->addField('email', 'Email', ValidForm::VFORM_EMAIL, [], [], [
            'dynamic' => true,
            'dynamicLabel' => 'Add email',
        ]);

        $_REQUEST['email'] = 'first@example.com';
        $_REQUEST['email_1'] = 'second@example.com';
        $_REQUEST['email_dynamic'] = '1';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('first@example.com', $html);
        $this->assertStringContainsString('second@example.com', $html);
    }

    // --------------------------------------------------------------
    // valuesAsHtml — multifields
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlRendersMultiFieldValuesOnOneRow(): void
    {
        $form = new ValidForm('test');
        $multi = $form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $_REQUEST['first-name'] = 'Robin';
        $_REQUEST['last-name'] = 'van Baalen';
        $form->isValid();

        $html = $form->valuesAsHtml();

        // Sub-values are joined with a space on a single overview row.
        $this->assertStringContainsString('Full name', $html);
        $this->assertStringContainsString('<strong>Robin van Baalen</strong>', $html);
    }

    #[Test]
    public function valuesAsHtmlRendersDynamicMultiFieldDuplicates(): void
    {
        // A dynamic multifield renders one row per dynamic count and skips
        // its internal hidden dynamic counter fields.
        $form = new ValidForm('test');
        $multi = $form->addMultiField('Full name', [
            'dynamic' => true,
            'dynamicLabel' => 'Add person',
        ]);
        $multi->addField('first-name', ValidForm::VFORM_STRING);
        $multi->addField('last-name', ValidForm::VFORM_STRING);

        $_REQUEST['first-name'] = 'John';
        $_REQUEST['last-name'] = 'Doe';
        $_REQUEST['first-name_1'] = 'Jane';
        $_REQUEST['last-name_1'] = 'Roe';
        $_REQUEST['first-name_dynamic'] = '1';
        $_REQUEST['last-name_dynamic'] = '1';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringContainsString('<strong>John Doe</strong>', $html);
        $this->assertStringContainsString('<strong>Jane Roe</strong>', $html);
    }

    // --------------------------------------------------------------
    // valuesAsHtml — XSS hardening
    // --------------------------------------------------------------

    #[Test]
    public function valuesAsHtmlEscapesSubmittedFieldValues(): void
    {
        // SECURITY: submitted values are escaped with htmlspecialchars
        // (ENT_QUOTES) before being echoed into the overview table, so an
        // injected payload is rendered inert. VFORM_STRING already rejects
        // angle brackets at validation time (defense in depth), so use
        // VFORM_HTML — the only type that allows them through validation.
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_HTML);

        $_REQUEST['name'] = '<script>alert("xss")</script>';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $html);
    }

    #[Test]
    public function valuesAsHtmlEscapesSubmittedMultiFieldValues(): void
    {
        // SECURITY: multifield values go through the same htmlspecialchars
        // (ENT_QUOTES) escaping as regular field values. VFORM_HTML is used
        // because it's the only type whose validation allows angle brackets.
        $form = new ValidForm('test');
        $multi = $form->addMultiField('Full name');
        $multi->addField('first-name', ValidForm::VFORM_HTML);

        $_REQUEST['first-name'] = '<img src=x onerror=alert(1)>';
        $form->isValid();

        $html = $form->valuesAsHtml();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    // --------------------------------------------------------------
    // toJs — custom javascript and subclass initialization
    // --------------------------------------------------------------

    #[Test]
    public function toJsAppendsCustomJavascript(): void
    {
        $form = new ValidForm('test');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->toJs("console.log('custom');");

        $this->assertStringContainsString("console.log('custom');", $js);
    }

    #[Test]
    public function toJsInitializesSubclassNameClientSide(): void
    {
        // When ValidForm is extended, the generated javascript tries to
        // initialize a client-side class with the same name and falls back
        // to ValidForm when it doesn't exist.
        $form = new ValidFormJsSubclass('subclass-form');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->renderRawJs();

        $this->assertStringContainsString('typeof ValidFormJsSubclass !== "undefined"', $js);
        $this->assertStringContainsString('new ValidFormJsSubclass("subclass-form", "")', $js);
        $this->assertStringContainsString('new ValidForm("subclass-form", "");', $js);
    }

    #[Test]
    public function toJsPassesInitArgumentsAsJson(): void
    {
        // Custom client-side classes can receive extra constructor
        // arguments, JSON encoded after the name and main alert.
        $form = new ValidFormJsSubclass('subclass-form');
        $form->addField('name', 'Name', ValidForm::VFORM_STRING);

        $js = $form->renderRawJs(['first-argument', 2]);

        $this->assertStringContainsString(
            'new ValidFormJsSubclass("subclass-form", "", ["first-argument",2])',
            $js
        );
    }

    // --------------------------------------------------------------
    // getStrippedClassName — input without namespace
    // --------------------------------------------------------------

    #[Test]
    public function getStrippedClassNameReturnsPlainClassNameUnchanged(): void
    {
        $this->assertSame('ValidForm', ValidForm::getStrippedClassName('ValidForm'));
    }
}

/**
 * Test-only ValidForm subclass.
 *
 * Exposes the protected {@link \ValidFormBuilder\ValidForm::__toJS()} method
 * so the subclass-detection and init-arguments code paths can be exercised.
 * The class name (with the test namespace stripped) ends up in the generated
 * javascript output.
 */
class ValidFormJsSubclass extends ValidForm
{
    public function renderRawJs(array $arrInitArguments = []): string
    {
        return $this->__toJS('', $arrInitArguments, true);
    }
}
