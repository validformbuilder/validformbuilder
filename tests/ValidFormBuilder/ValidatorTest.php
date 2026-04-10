<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Validator;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Validator}.
 *
 * Validator is a static utility class holding the regex lookup table and the
 * core `validate($checkType, $value)` dispatcher. It is the single chokepoint
 * for every server-side type check in the library.
 *
 * Surface covered:
 * - validate() with each built-in VFORM_* type (positive + negative inputs).
 * - validate() with custom regex (the else-branch for user-defined patterns).
 * - validate() with empty check type (returns true — no validation).
 * - validate() with array input (regression for #199 exit() fix).
 * - getCheck() returns the correct regex for each type.
 *
 * Security audit:
 * - Regression for #199 (exit → return false on invalid array element).
 * - Regression for #200 (VFORM_BOOLEAN regex tightened to `(on)?`).
 * - Custom validation regex uses `@preg_match` (error suppression) — documented.
 */
class ValidatorTest extends TestCase
{
    // --------------------------------------------------------------
    // validate() — per-type positive/negative cases
    // --------------------------------------------------------------

    #[Test]
    #[DataProvider('builtInTypeProvider')]
    public function validateAcceptsValidInputForBuiltInType(int $type, string $validInput): void
    {
        $this->assertTrue(
            (bool) Validator::validate($type, $validInput),
            "Expected '$validInput' to PASS validation for type $type"
        );
    }

    #[Test]
    #[DataProvider('builtInTypeRejectionProvider')]
    public function validateRejectsInvalidInputForBuiltInType(int $type, string $invalidInput): void
    {
        $this->assertFalse(
            (bool) Validator::validate($type, $invalidInput),
            "Expected '$invalidInput' to FAIL validation for type $type"
        );
    }

    public static function builtInTypeProvider(): array
    {
        return [
            'string: plain text'       => [ValidForm::VFORM_STRING, 'Hello world 123'],
            'string: empty'            => [ValidForm::VFORM_STRING, ''],
            'string: unicode'          => [ValidForm::VFORM_STRING, 'àáâãäåāæçèéêẽëē'],
            'word: simple'             => [ValidForm::VFORM_WORD, 'hello-world_123'],
            'word: empty'              => [ValidForm::VFORM_WORD, ''],
            'email: standard'          => [ValidForm::VFORM_EMAIL, 'user@example.com'],
            'email: subdomain'         => [ValidForm::VFORM_EMAIL, 'user@sub.example.co.uk'],
            'email: plus tag'          => [ValidForm::VFORM_EMAIL, 'user+tag@example.com'],
            'numeric: integer'         => [ValidForm::VFORM_NUMERIC, '42'],
            'numeric: negative'        => [ValidForm::VFORM_NUMERIC, '-3.14'],
            'numeric: european'        => [ValidForm::VFORM_NUMERIC, '1.234,56'],
            'numeric: empty'           => [ValidForm::VFORM_NUMERIC, ''],
            'integer: digits'          => [ValidForm::VFORM_INTEGER, '12345'],
            'integer: empty'           => [ValidForm::VFORM_INTEGER, ''],
            'boolean: empty (unchecked)' => [ValidForm::VFORM_BOOLEAN, ''],
            'boolean: on (checked)'    => [ValidForm::VFORM_BOOLEAN, 'on'],
            'boolean: ON (case)'       => [ValidForm::VFORM_BOOLEAN, 'ON'],
            'url: full'                => [ValidForm::VFORM_URL, 'https://example.com/path?q=1'],
            'url: no protocol'         => [ValidForm::VFORM_URL, 'example.com'],
            'simpleurl: domain'        => [ValidForm::VFORM_SIMPLEURL, 'example.com'],
            'date: dd-mm-yyyy'         => [ValidForm::VFORM_DATE, '01-12-2026'],
            'date: dd/mm/yyyy'         => [ValidForm::VFORM_DATE, '01/12/2026'],
            // Types with empty regex — anything passes
            'password: anything'       => [ValidForm::VFORM_PASSWORD, 's3cr3t!@#$%^&*()'],
            'select_list: anything'    => [ValidForm::VFORM_SELECT_LIST, 'any value'],
            'radio_list: anything'     => [ValidForm::VFORM_RADIO_LIST, 'any value'],
            'check_list: anything'     => [ValidForm::VFORM_CHECK_LIST, 'any value'],
            'hidden: anything'         => [ValidForm::VFORM_HIDDEN, 'any value'],
        ];
    }

    public static function builtInTypeRejectionProvider(): array
    {
        return [
            'string: angle brackets'   => [ValidForm::VFORM_STRING, '<script>alert(1)</script>'],
            'word: spaces'             => [ValidForm::VFORM_WORD, 'hello world'],
            'email: no domain'         => [ValidForm::VFORM_EMAIL, 'user@'],
            'email: no at sign'        => [ValidForm::VFORM_EMAIL, 'nodomain.com'],
            'numeric: letters'         => [ValidForm::VFORM_NUMERIC, 'abc'],
            'integer: decimal'         => [ValidForm::VFORM_INTEGER, '3.14'],
            'integer: letters'         => [ValidForm::VFORM_INTEGER, 'abc'],
            'boolean: o (single char)' => [ValidForm::VFORM_BOOLEAN, 'o'],
            'boolean: nooo (garbage)'  => [ValidForm::VFORM_BOOLEAN, 'nooo'],
            'boolean: true (not HTML)' => [ValidForm::VFORM_BOOLEAN, 'true'],
            'boolean: 1 (not HTML)'    => [ValidForm::VFORM_BOOLEAN, '1'],
            'date: invalid format'     => [ValidForm::VFORM_DATE, '2026-12-01'],
            'date: letters'            => [ValidForm::VFORM_DATE, 'not-a-date'],
        ];
    }

    // --------------------------------------------------------------
    // validate() — custom regex
    // --------------------------------------------------------------

    #[Test]
    public function validateWithCustomRegexMatchesValue(): void
    {
        // When the checkType is not a known VFORM_* constant, Validator treats
        // it as a custom regex and runs preg_match($checkType, $value).
        $this->assertTrue((bool) Validator::validate('/^[A-Z]{3}$/', 'ABC'));
    }

    #[Test]
    public function validateWithCustomRegexRejectsNonMatchingValue(): void
    {
        $this->assertFalse((bool) Validator::validate('/^[A-Z]{3}$/', 'abc'));
    }

    #[Test]
    public function validateWithEmptyCheckTypeReturnsTrue(): void
    {
        // An empty custom check means "no validation" — everything passes.
        $this->assertTrue((bool) Validator::validate('', 'anything'));
    }

    // --------------------------------------------------------------
    // validate() — array input (regression for #199)
    // --------------------------------------------------------------

    #[Test]
    public function validateArrayOfValidValuesReturnsTrue(): void
    {
        $this->assertTrue((bool) Validator::validate(ValidForm::VFORM_INTEGER, ['10', '20', '30']));
    }

    #[Test]
    public function validateArrayWithInvalidElementReturnsFalseWithoutExiting(): void
    {
        // SECURITY regression for #199: this used to call exit(), terminating
        // the entire PHP process. Now returns false cleanly.
        $result = Validator::validate(ValidForm::VFORM_INTEGER, ['10', 'abc', '30']);

        // If we reach this line, the process survived. Prior to the fix, it wouldn't.
        $this->assertFalse((bool) $result);
    }

    #[Test]
    public function validateArrayWithTypeHavingEmptyRegexReturnsTrue(): void
    {
        // VFORM_PASSWORD has an empty regex → array branch is skipped, returns true.
        $this->assertTrue((bool) Validator::validate(ValidForm::VFORM_PASSWORD, ['a', 'b']));
    }

    // --------------------------------------------------------------
    // getCheck()
    // --------------------------------------------------------------

    #[Test]
    public function getCheckReturnsRegexForKnownType(): void
    {
        $check = Validator::getCheck(ValidForm::VFORM_INTEGER);

        $this->assertSame('/^[0-9]*$/i', $check);
    }

    #[Test]
    public function getCheckReturnsEmptyStringForPasswordType(): void
    {
        // VFORM_PASSWORD has an empty regex — all values pass client-side,
        // server-side length/matchWith rules enforce constraints instead.
        $this->assertSame('', Validator::getCheck(ValidForm::VFORM_PASSWORD));
    }

    #[Test]
    public function getCheckReturnsEmptyStringForUnknownType(): void
    {
        $this->assertSame('', Validator::getCheck(99999));
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function stringTypeRejectsHtmlTagsPreventingStoredXss(): void
    {
        // SECURITY: VFORM_STRING's regex rejects `<` and `>`, so form
        // submissions containing HTML tags fail validation before they can
        // be stored in a database and rendered elsewhere.
        $this->assertFalse((bool) Validator::validate(ValidForm::VFORM_STRING, '<img src=x onerror=alert(1)>'));
    }

    #[Test]
    public function integerTypeRejectsSqlInjectionPayloads(): void
    {
        // SECURITY: integer fields only accept digits, so SQL injection
        // payloads are rejected at the type-check level.
        $this->assertFalse((bool) Validator::validate(ValidForm::VFORM_INTEGER, "1; DROP TABLE users;--"));
    }

    #[Test]
    public function customRegexWithErrorIsSuppressedViaAtSign(): void
    {
        // Validator uses @preg_match for custom regexes (line 92). An invalid
        // regex should not emit a PHP warning — it returns false silently.
        // This is acceptable: the developer set an invalid regex, and the
        // validator treats it as "no match" rather than crashing.
        $result = @Validator::validate('/invalid[regex/', 'test');

        $this->assertFalse((bool) $result);
    }
}
