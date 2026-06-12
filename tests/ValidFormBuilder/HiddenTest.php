<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Hidden;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Hidden}.
 *
 * Hidden is a thin Element subclass for `<input type="hidden">` fields.
 * It differs from other elements in that: it has no label, its toHtml()
 * renders a bare hidden input (no wrapper div), its toJS() emits only
 * condition logic (no objForm.addElement call), and it overrides
 * isDynamicCounter() to read the actual `__dynamiccounter` property
 * (used by dynamic field infrastructure to store the number of copies).
 *
 * Surface covered:
 * - Constructor: name, type, meta (no label, no rules, no errorHandlers).
 * - toHtml(): hidden input with escaped value, name, id, field-meta string.
 * - toJS(): empty string for basic hidden fields (no validation JS).
 * - hasFields(): always false.
 * - isDynamicCounter(): false by default, true when dynamicCounter meta set.
 * - isValid(): basic and dynamic-counter loop; documents parameter-ignored
 *   issue (the $intCount parameter is overwritten by the internal for-loop).
 *
 * Security audit:
 * - Value htmlspecialchars escaping in toHtml (XSS prevention).
 * - Attacker-controlled hidden values must not bypass type validation.
 */
class HiddenTest extends TestCase
{
    use HtmlAssertionsTrait;

    protected function tearDown(): void
    {
        foreach (['secret', 'counter', 'tampered'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresNameAndTypeWithEmptyLabel(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        $this->assertSame('secret', $field->getName());
        $this->assertSame(ValidForm::VFORM_STRING, $field->getType());
        $this->assertSame('', $field->getLabel());
    }

    #[Test]
    public function constructorAppliesDefaultFromMeta(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING, ['default' => 'preset']);

        $this->assertSame('preset', $field->getDefault());
    }

    // --------------------------------------------------------------
    // toHtml
    // --------------------------------------------------------------

    #[Test]
    public function toHtmlRendersHiddenInputWithNameAndId(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING, ['default' => 'myvalue']);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="hidden"]` — the single hidden input element.
        $input = $xpath->query('//input[@type="hidden"]')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('secret', $input->getAttribute('name'));
        $this->assertSame('secret', $input->getAttribute('id'));
        $this->assertSame('myvalue', $input->getAttribute('value'));
    }

    #[Test]
    public function toHtmlRendersSubmittedValueWhenFormIsSubmitted(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING, ['default' => 'original']);
        $_REQUEST['secret'] = 'submitted';

        $xpath = $this->parseHtml($field->toHtml(true));

        // `//input[@type="hidden"]` — value should reflect the submitted value, not the default.
        $input = $xpath->query('//input[@type="hidden"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('submitted', $input->getAttribute('value'));
    }

    #[Test]
    public function toHtmlDoesNotRenderWrapperDivOrLabel(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($field->toHtml());

        // Hidden fields render a bare <input>, no wrapping <div> or <label>.
        // `//div` — expect zero wrapper divs.
        $this->assertSame(0, $xpath->query('//div')->length);
        // `//label` — expect zero labels.
        $this->assertSame(0, $xpath->query('//label')->length);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsReturnsEmptyStringForBasicHiddenField(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        // Hidden fields only emit condition JS (if any). A plain hidden field
        // with no conditions produces an empty string — no objForm.addElement call.
        $this->assertSame('', $field->toJS());
    }

    // --------------------------------------------------------------
    // hasFields / isDynamicCounter
    // --------------------------------------------------------------

    #[Test]
    public function hasFieldsReturnsFalse(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        $this->assertFalse($field->hasFields());
    }

    #[Test]
    public function isDynamicCounterReturnsFalseByDefault(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        $this->assertFalse($field->isDynamicCounter());
    }

    #[Test]
    public function isDynamicCounterReturnsTrueWhenMetaFlagIsSet(): void
    {
        $field = new Hidden('counter', ValidForm::VFORM_INTEGER, [
            'default' => 0,
            'dynamicCounter' => true,
        ]);

        $this->assertTrue($field->isDynamicCounter());
    }

    // --------------------------------------------------------------
    // isValid
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsTrueForOptionalHiddenFieldWithoutInput(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        $this->assertTrue($field->isValid());
    }

    #[Test]
    public function isValidReturnsTrueForHiddenFieldWithValidInput(): void
    {
        $field = new Hidden('secret', ValidForm::VFORM_STRING);
        $_REQUEST['secret'] = 'some-value';

        $this->assertTrue($field->isValid());
    }

    #[Test]
    public function isValidReturnsFalseForHiddenFieldWithInvalidTypeInput(): void
    {
        $field = new Hidden('counter', ValidForm::VFORM_INTEGER);
        $_REQUEST['counter'] = 'not-an-integer';

        $this->assertFalse($field->isValid());
    }

    #[Test]
    public function isValidParameterIsIgnoredByInternalLoop(): void
    {
        // KNOWN ISSUE: Hidden::isValid($intCount) declares an $intCount parameter
        // but immediately overwrites it with the for-loop initialiser on line 125:
        //   for ($intCount = 0; $intCount <= $intDynamicCount; $intCount++)
        // This means passing a specific position (e.g. isValid(3)) has no effect —
        // the method always validates from position 0.
        //
        // Element::isValid($intCount) uses the parameter to validate a single
        // position when non-null. Hidden's override silently breaks that contract.
        // We document the current behaviour here rather than fixing it, because
        // hidden fields are typically only validated internally by the form pipeline
        // which calls isValid() without arguments.
        $field = new Hidden('secret', ValidForm::VFORM_STRING);

        // Calling isValid(99) should NOT throw and should return the same result
        // as isValid() — the parameter is silently dropped.
        $this->assertSame($field->isValid(), $field->isValid(99));
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function valueIsHtmlEscapedToPreventXssInHiddenInput(): void
    {
        // SECURITY: hidden inputs are part of the DOM even though they're invisible.
        // An attacker who can influence the value (e.g. via a reflected parameter or
        // a database-stored payload) could inject HTML if the value isn't escaped.
        $field = new Hidden('tampered', ValidForm::VFORM_STRING, [
            'default' => '"><script>alert(1)</script>',
        ]);

        $xpath = $this->parseHtml($field->toHtml());

        // `//input[@type="hidden"]` — the hidden input element.
        $input = $xpath->query('//input[@type="hidden"]')->item(0);
        $this->assertNotNull($input);

        // The attack payload should be preserved as a literal attribute value (escaped),
        // not as injected HTML.
        $this->assertSame('"><script>alert(1)</script>', $input->getAttribute('value'));

        // `//script` — no <script> elements should appear in the parsed DOM.
        $this->assertSame(0, $xpath->query('//script')->length);
    }

    #[Test]
    public function hiddenIntegerFieldRejectsNonNumericSubmission(): void
    {
        // SECURITY: hidden fields are easily tampered by clients (browser devtools,
        // intercepting proxy). The type validation must still enforce the declared
        // type regardless of the field being "hidden". An attacker submitting
        // `counter='; DROP TABLE users;--` must fail VFORM_INTEGER validation.
        $field = new Hidden('counter', ValidForm::VFORM_INTEGER);
        $_REQUEST['counter'] = "'; DROP TABLE users;--";

        $this->assertFalse($field->isValid());
    }

    #[Test]
    public function hiddenStringFieldRejectsHtmlTagsViaTypeRegex(): void
    {
        // SECURITY: VFORM_STRING's regex rejects `<` and `>` characters, so even
        // though the value passes through htmlspecialchars in toHtml(), the validator
        // itself should reject script-tag payloads before they can be stored.
        $field = new Hidden('secret', ValidForm::VFORM_STRING);
        $_REQUEST['secret'] = '<script>alert(1)</script>';

        $this->assertFalse($field->isValid());
    }
}
