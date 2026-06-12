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
 * - No new XSS vectors found. Label, tip, error and dynamicRemoveLabel
 *   strings render unescaped, but those are developer-supplied, not
 *   user input. toJS() escapes error strings with addslashes(), which
 *   does not neutralise `</script>` — again developer-supplied only.
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
        unset(
            $_REQUEST['name'],
            $_REQUEST['email'],
            $_REQUEST['phone'],
            $_REQUEST['phone_1'],
            $_REQUEST['phone_dynamic']
        );
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
    // Error rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersErrorParagraphWhenSubmittedInvalid(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            ['required' => true],
            ['required' => 'Name is required']
        );

        // Submitted without a value in $_REQUEST — required validation fails.
        $xpath = $this->parseHtml($field->toHtml(true));

        // `//p[@class="vf__error"]` — the error paragraph above the input.
        $error = $xpath->query('//p[@class="vf__error"]')->item(0);
        $this->assertNotNull($error);
        $this->assertSame('Name is required', trim($error->textContent));

        // `//div` — the wrapper carries the vf__error class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);
    }

    #[Test]
    public function toHtmlWithoutLabelAddsNolabelClassAndOmitsLabel(): void
    {
        $field = $this->form->addField('name', 'Name', ValidForm::VFORM_STRING);

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
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['hint' => 'Your full name']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="text"]` — unsubmitted fields render the hint as value.
        $input = $xpath->query('//input[@type="text"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('Your full name', $input->getAttribute('value'));

        // `//div` — the wrapper carries the vf__hint class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__hint', $classTokens);
    }

    // --------------------------------------------------------------
    // Dynamic / removable fields
    // --------------------------------------------------------------

    #[Test]
    public function dynamicFieldRendersOriginalAndCloneMarkers(): void
    {
        $field = $this->form->addField(
            'phone',
            'Phone',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another phone']
        );
        $_REQUEST['phone_dynamic'] = '1';

        $xpath = $this->parseHtml($field->toHtml());

        // `//div[@data-dynamic="original"]` — the first field is the original.
        $original = $xpath->query('//div[@data-dynamic="original"]')->item(0);
        $this->assertNotNull($original);

        // `//div[@data-dynamic="clone"]` — the second field is a clone with vf__clone.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);

        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);

        // `//input[@name="phone_1"]` — the clone input gets the _1 suffix.
        $cloneInput = $xpath->query('//input[@name="phone_1"]')->item(0);
        $this->assertNotNull($cloneInput);
        $this->assertSame('phone_1', $cloneInput->getAttribute('id'));

        // `//div[@class="vf__dynamic"]/a` — the "add another" anchor after the last clone.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('Add another phone', trim($anchor->textContent));
        $this->assertSame('phone', $anchor->getAttribute('data-target-id'));
    }

    #[Test]
    public function removableFieldRendersRemoveLabelAnchor(): void
    {
        $field = $this->form->addField(
            'phone',
            'Phone',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another', 'dynamicRemoveLabel' => 'Remove this field']
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
        $this->assertSame('Remove this field', trim($remove->textContent));
    }

    // --------------------------------------------------------------
    // Simple layout (MultiField item rendering)
    // --------------------------------------------------------------

    #[Test]
    public function simpleLayoutRendersHintAndMultifielditemClasses(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['hint' => 'Your full name']
        );

        $xpath = $this->parseHtml($field->__toHtml(false, true));

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
            'name',
            'Name',
            ValidForm::VFORM_STRING,
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
            'phone',
            'Phone',
            ValidForm::VFORM_STRING,
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

        // `//input[@name="phone_1"]` — the clone input gets the _1 suffix.
        $this->assertSame(1, $xpath->query('//input[@name="phone_1"]')->length);
    }

    // --------------------------------------------------------------
    // toJS variants
    // --------------------------------------------------------------

    #[Test]
    public function toJsEncodesSanitisersWhenSet(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['sanitize' => ['trim']]
        );

        $js = $field->toJS();

        $this->assertStringContainsString('["trim"]', $js);
    }

    #[Test]
    public function toJsEncodesExternalJavascriptValidation(): void
    {
        $field = $this->form->addField(
            'name',
            'Name',
            ValidForm::VFORM_STRING,
            ['externalValidation' => ['javascript' => 'window.checkName']]
        );

        $js = $field->toJS();

        $this->assertStringContainsString('"window.checkName"', $js);
    }

    #[Test]
    public function toJsEmitsAddElementForEachDynamicField(): void
    {
        $field = $this->form->addField(
            'phone',
            'Phone',
            ValidForm::VFORM_STRING,
            ['required' => true],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );
        $_REQUEST['phone_dynamic'] = '1';

        $js = $field->toJS();

        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("objForm.addElement('phone'", $js);
        $this->assertStringContainsString("objForm.addElement('phone_1'", $js);

        // Dynamic fields are never required client-side once clones exist —
        // the required flag is forced to false for every occurrence.
        $this->assertMatchesRegularExpression("/addElement\('phone_1', 'phone_1', .+?, false, /", $js);
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
