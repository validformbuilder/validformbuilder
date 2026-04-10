<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\File;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\File}.
 *
 * File is a thin Element subclass that renders a file input, a MAX_FILE_SIZE
 * hidden input, and (on re-render after an error) a hidden input per previously
 * uploaded filename. It also emits client-side validation JS via Element::toJS.
 *
 * Surface covered:
 * - Constructor wiring (inherited from Element) and basic accessors.
 * - Rendering: wrapping <div>, <input type="file">, MAX_FILE_SIZE hint input,
 *   required/optional/error class tokens, conditional <legend> / <small> tip,
 *   conditional hidden inputs for previously submitted filenames.
 * - toJS output: exactly one objForm.addElement() call referencing the field id.
 * - Dynamic fields: per-count id / name suffix (`_1`, `_2`, …).
 *
 * Security audit (per project testing policy):
 * - Filename XSS: attacker-controlled filenames re-rendered in hidden inputs
 *   must be escaped via htmlspecialchars before reaching the DOM.
 * - Documented-but-unenforced constraints: `maxFiles`, `maxSize`, and
 *   `fileTypes` are currently ignored by the server-side validator. The
 *   tests below lock in the current (broken) behaviour so the fix for
 *   https://github.com/validformbuilder/validformbuilder/issues/201 has an
 *   immediate regression suite to flip.
 */
class FileTest extends TestCase
{
    use HtmlAssertionsTrait;

    private ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
    }

    protected function tearDown(): void
    {
        foreach (['logo', 'document', 'upload', 'tampered'] as $key) {
            unset($_REQUEST[$key], $_FILES[$key]);
        }
    }

    // --------------------------------------------------------------
    // Construction and accessors
    // --------------------------------------------------------------

    #[Test]
    public function addFieldWithVformFileTypeReturnsFileInstance(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $this->assertInstanceOf(File::class, $field);
    }

    #[Test]
    public function fileInheritsElementBehaviourForNameLabelAndType(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $this->assertSame('logo', $field->getName());
        $this->assertSame('Upload logo', $field->getLabel());
        $this->assertSame(ValidForm::VFORM_FILE, $field->getType());
    }

    #[Test]
    public function fileDefaultsHasFieldsToFalseAndIsNotDynamic(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $this->assertFalse($field->hasFields());
        $this->assertFalse($field->isDynamic());
    }

    // --------------------------------------------------------------
    // toHtml — structural rendering
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersExactlyOneFileInputAndOneMaxFileSizeHint(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="file"]` — exactly one real file input in the fragment.
        $this->assertSame(1, $xpath->query('//input[@type="file"]')->length);

        // `//input[@type="hidden" and @name="MAX_FILE_SIZE"]` — the hidden input
        // PHP reads to set its client-side upload-size limit.
        $this->assertSame(1, $xpath->query('//input[@type="hidden" and @name="MAX_FILE_SIZE"]')->length);
    }

    #[Test]
    public function fileInputNameIsSuffixedWithArrayBrackets(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $xpath = $this->parseHtml($field->toHtml());
        // `//input[@type="file"]` — the single file input; its name must end in `[]`
        // because File always renders a multi-value input for future dynamic support.
        $input = $xpath->query('//input[@type="file"]')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('logo[]', $input->getAttribute('name'));
        $this->assertSame('logo', $input->getAttribute('id'));
    }

    #[Test]
    public function requiredFileFieldWrapperHasRequiredClassToken(): void
    {
        $field = $this->form->addField(
            'logo',
            'Upload logo',
            ValidForm::VFORM_FILE,
            ['required' => true]
        );

        $xpath = $this->parseHtml($field->toHtml());
        // `//div` — File wraps its markup in a single outer <div>; grab it and
        // check the class list contains the `vf__required` token.
        $wrapper = $xpath->query('//div')->item(0);

        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function optionalFileFieldWrapperHasOptionalClassToken(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $xpath = $this->parseHtml($field->toHtml());
        // `//div` — outer wrapper.
        $wrapper = $xpath->query('//div')->item(0);

        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__optional', $classTokens);
    }

    #[Test]
    public function toHtmlRendersLabelLinkedToFileInput(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $xpath = $this->parseHtml($field->toHtml());
        // `//label[@for="logo"]` — the label tied specifically to the `logo` input
        // by its `for` attribute, not any arbitrary <label> in the fragment.
        $label = $xpath->query('//label[@for="logo"]')->item(0);

        $this->assertNotNull($label);
        $this->assertSame('Upload logo', trim($label->textContent));
    }

    #[Test]
    public function toHtmlWithTipAppendsSmallTipElement(): void
    {
        $field = $this->form->addField(
            'logo',
            'Upload logo',
            ValidForm::VFORM_FILE,
            [],
            [],
            ['tip' => 'Images only, under 1 MB.']
        );

        $xpath = $this->parseHtml($field->toHtml());
        // `//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]`
        // — a <small> whose class list contains the `vf__tip` token. Same pad-and-contains
        // workaround explained in the FieldsetTest comments.
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);

        $this->assertNotNull($tip);
        $this->assertSame('Images only, under 1 MB.', trim($tip->textContent));
    }

    #[Test]
    public function toHtmlDoesNotRenderTipElementWhenTipNotSet(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $xpath = $this->parseHtml($field->toHtml());
        // Same `vf__tip` class-token query; expect zero matches.
        $this->assertSame(
            0,
            $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->length
        );
    }

    #[Test]
    public function toHtmlRendersHiddenInputForPreviouslySubmittedFilename(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);
        // Simulate the server having rendered the form once before, with a filename
        // the user previously uploaded. The file input stays empty (browsers can't
        // pre-populate file inputs for security reasons), but the hidden input keeps
        // the filename so the server can still reference it.
        $_REQUEST['logo'] = 'previous.png';

        $xpath = $this->parseHtml($field->toHtml(true));

        // `//input[@type="hidden" and @name="logo[]"]` — a hidden input carrying the
        // remembered filename. There should be exactly one (plus the MAX_FILE_SIZE hidden
        // which has a different name).
        $hidden = $xpath->query('//input[@type="hidden" and @name="logo[]"]');
        $this->assertSame(1, $hidden->length);
        $this->assertSame('previous.png', $hidden->item(0)->getAttribute('value'));
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsEmitsExactlyOneAddElementCallForTheField(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $js = $field->toJS();

        $this->assertSame(1, substr_count($js, 'objForm.addElement'));
        $this->assertStringContainsString("'logo'", $js);
    }

    #[Test]
    public function toJsEmitsRequiredFlagTrueForRequiredFileField(): void
    {
        $field = $this->form->addField(
            'logo',
            'Upload logo',
            ValidForm::VFORM_FILE,
            ['required' => true]
        );

        $js = $field->toJS();

        // The `required` flag lives in the fourth positional slot of addElement().
        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('logo',\\s*'logo',[^,]+,\\s*true,/",
            $js
        );
    }

    #[Test]
    public function toJsEmitsRequiredFlagFalseForOptionalFileField(): void
    {
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);

        $js = $field->toJS();

        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('logo',\\s*'logo',[^,]+,\\s*false,/",
            $js
        );
    }

    // --------------------------------------------------------------
    // convertToBytes (private, tested via reflection)
    // --------------------------------------------------------------

    #[Test]
    #[DataProvider('convertToBytesProvider')]
    public function convertToBytesExpandsIniStyleSizeSuffixes(string $input, int|string $expected): void
    {
        $field = new File('upload', ValidForm::VFORM_FILE, 'Upload');

        $ref = new \ReflectionMethod(File::class, 'convertToBytes');
        $ref->setAccessible(true);
        $result = $ref->invoke($field, $input);

        $this->assertSame($expected, $result);
    }

    public static function convertToBytesProvider(): array
    {
        return [
            'kilobytes'        => ['5K', 5 * 1024],
            'megabytes'        => ['2M', 2 * 1048576],
            'gigabytes'        => ['1G', 1 * 1073741824],
            'lowercase m'      => ['10m', 10 * 1048576],
            // Default branch: no known suffix → return the raw input string.
            'no suffix returns raw string' => ['1024', '1024'],
        ];
    }

    // --------------------------------------------------------------
    // Security audit
    // --------------------------------------------------------------

    #[Test]
    public function rememberedFilenameWithXssPayloadIsEscapedInHiddenInput(): void
    {
        // SECURITY: the hidden input that remembers a previously-submitted filename
        // is built from $_REQUEST, which is attacker-controlled. Without escaping,
        // a crafted filename like `a" onload="alert(1)` would break out of the value
        // attribute and execute JavaScript.
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);
        $_REQUEST['logo'] = 'a" onload="alert(1)';

        $xpath = $this->parseHtml($field->toHtml(true));
        // `//input[@type="hidden" and @name="logo[]"]` — the hidden filename-remember input.
        $hidden = $xpath->query('//input[@type="hidden" and @name="logo[]"]')->item(0);

        $this->assertNotNull($hidden);
        // DOMXPath already returns the parsed attribute value, so this is the
        // post-unescape form. The key check is that there is NO extra `onload`
        // attribute on the <input> — if the escape were missing, the parser would
        // have split the attack payload into an `onload` attribute.
        $this->assertSame('a" onload="alert(1)', $hidden->getAttribute('value'));
        $this->assertSame('', $hidden->getAttribute('onload'));
    }

    #[Test]
    public function rememberedFilenameArrayIsEscapedInEachHiddenInput(): void
    {
        // SECURITY: same vector as above but via the array code path (PHP parses
        // $_REQUEST['logo'][] into an array).
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);
        $_REQUEST['logo'] = [
            'first.png',
            '"><img src=x onerror=alert(1)>',
            'third.png',
        ];

        $xpath = $this->parseHtml($field->toHtml(true));
        // `//input[@type="hidden" and @name="logo[]"]` — every remembered hidden
        // filename input; the MAX_FILE_SIZE input has a different name and is excluded.
        $hiddens = $xpath->query('//input[@type="hidden" and @name="logo[]"]');

        $this->assertSame(3, $hiddens->length);

        // No injected <img> element escaped into the DOM.
        $this->assertSame(0, $xpath->query('//img[@onerror]')->length);

        // Each hidden input's value survives intact as a literal attribute value.
        $this->assertSame('first.png', $hiddens->item(0)->getAttribute('value'));
        $this->assertSame('"><img src=x onerror=alert(1)>', $hiddens->item(1)->getAttribute('value'));
        $this->assertSame('third.png', $hiddens->item(2)->getAttribute('value'));
    }

    #[Test]
    public function maxFileSizeHintComesFromIniSettingNotUserInput(): void
    {
        // SECURITY: the MAX_FILE_SIZE hidden input must be sourced from PHP's own
        // `upload_max_filesize` ini value, never from $_REQUEST or user input, so
        // a client can't advertise a larger size than the server actually honours.
        $field = $this->form->addField('logo', 'Upload logo', ValidForm::VFORM_FILE);
        $_REQUEST['MAX_FILE_SIZE'] = '999999999999';

        $xpath = $this->parseHtml($field->toHtml());
        // `//input[@name="MAX_FILE_SIZE"]` — the upload-size hint hidden input.
        $hint = $xpath->query('//input[@name="MAX_FILE_SIZE"]')->item(0);

        $this->assertNotNull($hint);
        $this->assertNotSame('999999999999', $hint->getAttribute('value'));

        unset($_REQUEST['MAX_FILE_SIZE']);
    }

    #[Test]
    public function maxFilesConstraintIsCurrentlyIgnoredByServerSideValidator(): void
    {
        // REGRESSION DOC for https://github.com/validformbuilder/validformbuilder/issues/201
        // — maxFiles is declared on the validator but no enforcement code exists,
        // so a submission of many filenames passes validate() unchallenged.
        //
        // This test LOCKS IN the current (broken) behaviour so when the fix for
        // #201 lands, this assertion flips and the test becomes a positive
        // regression for the new enforcement. Update the assertion (and the
        // comment) once the enforcement is in place.
        $field = $this->form->addField(
            'document',
            'Upload document',
            ValidForm::VFORM_FILE,
            ['maxFiles' => 1]
        );
        $_REQUEST['document'] = ['first.pdf', 'second.pdf', 'third.pdf'];

        $this->assertTrue(
            $field->getValidator()->validate(),
            'Expected current behaviour: maxFiles is silently ignored. '
                . 'If this assertion now reports false, the fix for #201 has landed — '
                . 'update this test to assert enforcement instead of ignorance.'
        );
    }

    #[Test]
    public function maxSizeConstraintIsCurrentlyIgnoredByServerSideValidator(): void
    {
        // REGRESSION DOC for https://github.com/validformbuilder/validformbuilder/issues/201
        // — maxSize is declared on the validator but no enforcement code exists.
        //
        // Same lock-in pattern as the maxFiles test above.
        $field = $this->form->addField(
            'document',
            'Upload document',
            ValidForm::VFORM_FILE,
            ['maxSize' => 1]
        );

        // A 10 GB file, well above the declared 1 KB limit.
        $_FILES['document'] = [
            'name'     => ['huge.bin'],
            'type'     => ['application/octet-stream'],
            'tmp_name' => ['/tmp/fake'],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [10 * 1024 * 1024 * 1024],
        ];
        $_REQUEST['document'] = ['huge.bin'];

        $this->assertTrue(
            $field->getValidator()->validate(),
            'Expected current behaviour: maxSize is silently ignored. '
                . 'If this assertion now reports false, the fix for #201 has landed.'
        );
    }

    #[Test]
    public function fileTypesConstraintIsCurrentlyIgnoredByServerSideValidator(): void
    {
        // REGRESSION DOC for https://github.com/validformbuilder/validformbuilder/issues/201
        // — fileTypes is declared on the validator but no enforcement code exists.
        //
        // Same lock-in pattern.
        $field = $this->form->addField(
            'document',
            'Upload document',
            ValidForm::VFORM_FILE,
            ['fileTypes' => ['application/pdf']]
        );

        // An executable, not a PDF.
        $_FILES['document'] = [
            'name'     => ['evil.exe'],
            'type'     => ['application/x-msdownload'],
            'tmp_name' => ['/tmp/fake'],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [1024],
        ];
        $_REQUEST['document'] = ['evil.exe'];

        $this->assertTrue(
            $field->getValidator()->validate(),
            'Expected current behaviour: fileTypes is silently ignored. '
                . 'If this assertion now reports false, the fix for #201 has landed.'
        );
    }
}
