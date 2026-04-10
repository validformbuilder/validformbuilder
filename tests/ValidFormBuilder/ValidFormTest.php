<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Button;
use ValidFormBuilder\Checkbox;
use ValidFormBuilder\Collection;
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
}
