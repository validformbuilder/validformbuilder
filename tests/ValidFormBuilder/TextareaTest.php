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
 *   (both default and submitted values — see security tests below)
 * - No new XSS vectors found. Label, tip, error and dynamicRemoveLabel
 *   strings render unescaped, but those are developer-supplied, not
 *   user input.
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
        unset(
            $_REQUEST['bio'],
            $_REQUEST['notes'],
            $_REQUEST['bio_1'],
            $_REQUEST['bio_dynamic']
        );
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
    // Wrapper classes and error rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersRequiredClassWhenRequired(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            ['required' => true]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//div` — the outer wrapper.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function toHtmlRendersErrorParagraphWhenSubmittedInvalid(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            ['required' => true],
            ['required' => 'Bio is required']
        );

        // Submitted without a value in $_REQUEST — required validation fails.
        $xpath = $this->parseHtml($field->toHtml(true));

        // `//p[@class="vf__error"]` — the error paragraph above the textarea.
        $error = $xpath->query('//p[@class="vf__error"]')->item(0);
        $this->assertNotNull($error);
        $this->assertSame('Bio is required', trim($error->textContent));

        // `//div` — the wrapper carries the vf__error class.
        $wrapper = $xpath->query('//div')->item(0);
        $this->assertNotNull($wrapper);

        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);
    }

    #[Test]
    public function toHtmlWithoutLabelAddsNolabelClassAndOmitsLabel(): void
    {
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);

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
    public function toHtmlRendersHintAsContentWithHintClass(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['hint' => 'Tell us about yourself']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — unsubmitted fields render the hint as content.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);
        $this->assertSame('Tell us about yourself', trim($textarea->textContent));

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
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            ['maxLength' => 500]
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//textarea` — should carry a maxlength attribute.
        $textarea = $xpath->query('//textarea')->item(0);
        $this->assertNotNull($textarea);
        $this->assertSame('500', $textarea->getAttribute('maxlength'));
    }

    #[Test]
    public function toHtmlRendersTipWhenSet(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['tip' => 'Keep it short']
        );

        $xpath = $this->parseHtml($field->toHtml());

        // `//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]`
        // — the tip element.
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);
        $this->assertNotNull($tip);
        $this->assertSame('Keep it short', trim($tip->textContent));
    }

    // --------------------------------------------------------------
    // Dynamic / removable fields
    // --------------------------------------------------------------

    #[Test]
    public function dynamicFieldRendersOriginalAndCloneMarkers(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another bio']
        );
        $_REQUEST['bio_dynamic'] = '1';

        $xpath = $this->parseHtml($field->toHtml());

        // `//div[@data-dynamic="original"]` — the first field is the original.
        $original = $xpath->query('//div[@data-dynamic="original"]')->item(0);
        $this->assertNotNull($original);

        // `//div[@data-dynamic="clone"]` — the second field is a clone with vf__clone.
        $clone = $xpath->query('//div[@data-dynamic="clone"]')->item(0);
        $this->assertNotNull($clone);

        $classTokens = preg_split('/\s+/', (string) $clone->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $classTokens);

        // `//textarea[@name="bio_1"]` — the clone textarea gets the _1 suffix.
        $cloneTextarea = $xpath->query('//textarea[@name="bio_1"]')->item(0);
        $this->assertNotNull($cloneTextarea);
        $this->assertSame('bio_1', $cloneTextarea->getAttribute('id'));

        // `//div[@class="vf__dynamic"]/a` — the "add another" anchor after the last clone.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('Add another bio', trim($anchor->textContent));
    }

    #[Test]
    public function removableFieldRendersRemoveLabelAnchor(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another', 'dynamicRemoveLabel' => 'Remove this bio']
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
        $this->assertSame('Remove this bio', trim($remove->textContent));
    }

    // --------------------------------------------------------------
    // Simple layout (MultiField item rendering)
    // --------------------------------------------------------------

    #[Test]
    public function simpleLayoutRendersHintAndMultifielditemClasses(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            [],
            [],
            ['hint' => 'Tell us about yourself']
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
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
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
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
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

        // `//textarea[@name="bio_1"]` — the clone textarea gets the _1 suffix.
        $this->assertSame(1, $xpath->query('//textarea[@name="bio_1"]')->length);
    }

    // --------------------------------------------------------------
    // toJS variants
    // --------------------------------------------------------------

    #[Test]
    public function toJsEncodesSanitisersWhenSet(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
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
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            ['externalValidation' => ['javascript' => 'window.checkBio']]
        );

        $js = $field->toJS();

        $this->assertStringContainsString('"window.checkBio"', $js);
    }

    #[Test]
    public function toJsEmitsAddElementForEachDynamicField(): void
    {
        $field = $this->form->addField(
            'bio',
            'Bio',
            ValidForm::VFORM_TEXT,
            ['required' => true],
            [],
            ['dynamic' => true, 'dynamicLabel' => 'Add another']
        );
        $_REQUEST['bio_dynamic'] = '1';

        $js = $field->toJS();

        $this->assertSame(2, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("objForm.addElement('bio'", $js);
        $this->assertStringContainsString("objForm.addElement('bio_1'", $js);

        // Dynamic fields are never required client-side once clones exist —
        // the required flag is forced to false for every occurrence.
        $this->assertMatchesRegularExpression("/addElement\('bio_1', 'bio_1', .+?, false, /", $js);
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function submittedValueIsHtmlEscapedInTextareaContent(): void
    {
        // SECURITY: submitted (user-controlled) values flow through the same
        // htmlspecialchars(ENT_QUOTES) escape as defaults before re-render.
        $field = $this->form->addField('bio', 'Bio', ValidForm::VFORM_TEXT);
        $_REQUEST['bio'] = '</textarea><script>alert(1)</script>';

        $xpath = $this->parseHtml($field->toHtml(true));

        // `//textarea` — the textarea survives the breakout attempt intact.
        $this->assertSame(1, $xpath->query('//textarea')->length);

        // `//script` — no injected script elements.
        $this->assertSame(0, $xpath->query('//script')->length);
    }

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
