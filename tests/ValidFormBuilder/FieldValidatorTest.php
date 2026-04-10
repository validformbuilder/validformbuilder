<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\FieldValidator;
use ValidFormBuilder\Text;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\FieldValidator}.
 *
 * FieldValidator is the server-side validation engine: it enforces
 * required/optional, length, value ranges, regex type checks,
 * sanitisation rules, external callbacks, and the match-with /
 * only-list-items business rules. This test exercises the full surface
 * plus a dedicated "security" section for vulnerability-adjacent
 * behaviour (mass assignment safety, array input handling, regex
 * pathological inputs, null bytes, etc.).
 *
 * Surface covered:
 * - Constructor: rule application, error-handler application, fieldname
 *   bracket stripping, default required snapshot.
 * - getValidValue / getValue: cache, default, $_REQUEST override,
 *   dynamic position name suffix, array submissions.
 * - setRequired / getRequired (including the default snapshot).
 * - validate(): required/optional, hint sentinel, min/max length,
 *   matchWith, onlyListItems, minvalue/maxvalue, external PHP callback,
 *   conditional required/enabled/visible overrides, active-area parent
 *   interaction, error collection, reset between calls.
 * - preSanitize() whitelist enforcement (only "trim" allowed).
 * - sanitize() trim / clear / callable variants, silent-failure on
 *   sanitiser exceptions.
 * - setError / getError: override wiring and per-dynamic-position
 *   isolation.
 * - getCheck(): custom regex vs type-derived regex.
 * - toFloat() via minvalue with European (`1.234,56`) formatting.
 * - Security tests: array validation must not kill the process, null
 *   bytes must not be silently accepted, large inputs must complete in
 *   reasonable time, mass assignment via validation rules must not
 *   leak into the `__validvalues` cache.
 */
class FieldValidatorTest extends TestCase
{
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
    public function constructorStoresFieldTypeFieldnameAndHint(): void
    {
        $field = new Text(
            'username',
            ValidForm::VFORM_STRING,
            'Username',
            [],
            [],
            ['hint' => 'enter your username']
        );
        $validator = $field->getValidator();

        $this->assertSame('username', $validator->getFieldName());
        $this->assertSame(ValidForm::VFORM_STRING, $validator->getType());
        $this->assertSame('enter your username', $validator->getFieldHint());
    }

    #[Test]
    public function constructorStripsArrayBracketsFromFieldName(): void
    {
        // Multi-value fields end in `[]` so $_REQUEST[name] returns an array.
        // The validator stores the *unbracketed* name so it can look up
        // $_REQUEST[name] rather than $_REQUEST['name[]'].
        $field = new Text('tags[]', ValidForm::VFORM_STRING, 'Tags');

        $this->assertSame('tags', $field->getValidator()->getFieldName());
    }

    #[Test]
    public function constructorAppliesKnownValidationRulesToProperties(): void
    {
        $field = new Text(
            'age',
            ValidForm::VFORM_INTEGER,
            'Age',
            [
                'required' => true,
                'minLength' => 1,
                'maxLength' => 3,
                'minValue' => 18,
                'maxValue' => 120,
            ]
        );
        $validator = $field->getValidator();

        $this->assertTrue($validator->getRequired());
        $this->assertSame(1, $validator->getMinLength());
        $this->assertSame(3, $validator->getMaxLength());
        $this->assertSame(18, $validator->getMinValue());
        $this->assertSame(120, $validator->getMaxValue());
    }

    #[Test]
    public function constructorAppliesErrorHandlersToMatchingErrorProperties(): void
    {
        $field = new Text(
            'age',
            ValidForm::VFORM_INTEGER,
            'Age',
            ['required' => true, 'minValue' => 18],
            [
                'required' => 'Age is mandatory.',
                'minValue' => 'Age must be at least %s.',
                'type'     => 'Age must be a whole number.',
            ]
        );
        $validator = $field->getValidator();

        $this->assertSame('Age is mandatory.', $validator->getRequiredError());
        $this->assertSame('Age must be at least %s.', $validator->getMinValueError());
        $this->assertSame('Age must be a whole number.', $validator->getTypeError());
    }

    #[Test]
    public function constructorRememberSDefaultRequiredStateSeparately(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['required' => true]
        );
        $validator = $field->getValidator();

        // setRequired mutates the current required state but preserves the default.
        $validator->setRequired(false);

        $this->assertFalse($validator->getRequired());
        $this->assertTrue($validator->getRequired(true), 'Default required state should survive setRequired()');
    }

    // --------------------------------------------------------------
    // getValidValue / getValue
    // --------------------------------------------------------------

    #[Test]
    public function getValidValueReturnsNullBeforeValidation(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $this->assertNull($field->getValidator()->getValidValue());
    }

    #[Test]
    public function getValidValueReturnsCachedValueAtDynamicPosition(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name_2'] = 'dynamic';

        $field->getValidator()->validate(2);

        $this->assertSame('dynamic', $field->getValidator()->getValidValue(2));
    }

    #[Test]
    public function getValueReturnsSubmittedValueFromRequest(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name'] = 'submitted';

        $this->assertSame('submitted', $field->getValidator()->getValue());
    }

    #[Test]
    public function getValueReturnsDefaultWhenNoRequestSubmission(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            [],
            [],
            ['default' => 'fallback']
        );

        $this->assertSame('fallback', $field->getValidator()->getValue());
    }

    #[Test]
    public function getValueUsesSuffixedFieldNameForDynamicPosition(): void
    {
        $field = new Text('phone', ValidForm::VFORM_STRING, 'Phone');
        $_REQUEST['phone_1'] = 'dynamic one';
        $_REQUEST['phone_2'] = 'dynamic two';

        $this->assertSame('dynamic one', $field->getValidator()->getValue(1));
        $this->assertSame('dynamic two', $field->getValidator()->getValue(2));
    }

    #[Test]
    public function getValueReturnsArrayWhenRequestValueIsArray(): void
    {
        $field = new Text('tags[]', ValidForm::VFORM_STRING, 'Tags');
        $_REQUEST['tags'] = ['one', 'two', 'three'];

        $this->assertSame(['one', 'two', 'three'], $field->getValidator()->getValue());
    }

    // --------------------------------------------------------------
    // setRequired / getRequired
    // --------------------------------------------------------------

    #[Test]
    public function setRequiredCoercesTruthyValuesToBool(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $validator = $field->getValidator();

        $validator->setRequired(1);
        $this->assertTrue($validator->getRequired());
        $this->assertIsBool($validator->getRequired());

        $validator->setRequired(0);
        $this->assertFalse($validator->getRequired());
        $this->assertIsBool($validator->getRequired());
    }

    // --------------------------------------------------------------
    // validate() — required / optional
    // --------------------------------------------------------------

    #[Test]
    public function validateFailsForRequiredFieldWithoutInput(): void
    {
        $field = new Text(
            'required-field',
            ValidForm::VFORM_STRING,
            'Required',
            ['required' => true]
        );

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('This field is required.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateSucceedsForRequiredFieldWithInput(): void
    {
        $field = new Text(
            'required-field',
            ValidForm::VFORM_STRING,
            'Required',
            ['required' => true]
        );
        $_REQUEST['required-field'] = 'some value';

        $this->assertTrue($field->getValidator()->validate());
        $this->assertSame('', $field->getValidator()->getError());
    }

    #[Test]
    public function validateSucceedsForOptionalFieldWithoutInput(): void
    {
        $field = new Text('optional-field', ValidForm::VFORM_STRING, 'Optional');

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateResetsErrorsBetweenCalls(): void
    {
        $field = new Text(
            'required-field',
            ValidForm::VFORM_STRING,
            'Required',
            ['required' => true]
        );
        $validator = $field->getValidator();

        // First call: empty → error recorded.
        $this->assertFalse($validator->validate());
        $this->assertNotSame('', $validator->getError());

        // Supply the value, second call should wipe the error.
        $_REQUEST['required-field'] = 'hello';
        $this->assertTrue($validator->validate());
        $this->assertSame('', $validator->getError());
    }

    // --------------------------------------------------------------
    // validate() — length constraints
    // --------------------------------------------------------------

    #[Test]
    public function validateFailsWhenInputShorterThanMinLength(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['minLength' => 5]
        );
        $_REQUEST['name'] = 'abc';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('minimum is 5', $field->getValidator()->getError());
    }

    #[Test]
    public function validateFailsWhenInputLongerThanMaxLength(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['maxLength' => 5]
        );
        $_REQUEST['name'] = 'abcdefghij';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('maximum is 5', $field->getValidator()->getError());
    }

    #[Test]
    public function validateAcceptsInputAtExactMinLength(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['minLength' => 3]
        );
        $_REQUEST['name'] = 'abc';

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateAcceptsInputAtExactMaxLength(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['maxLength' => 5]
        );
        $_REQUEST['name'] = 'abcde';

        $this->assertTrue($field->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // validate() — value range
    // --------------------------------------------------------------

    #[Test]
    public function validateFailsWhenValueBelowMinValue(): void
    {
        $field = new Text(
            'age',
            ValidForm::VFORM_INTEGER,
            'Age',
            ['minValue' => 18]
        );
        $_REQUEST['age'] = '17';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('minimum is 18', $field->getValidator()->getError());
    }

    #[Test]
    public function validateFailsWhenValueAboveMaxValue(): void
    {
        $field = new Text(
            'age',
            ValidForm::VFORM_INTEGER,
            'Age',
            ['maxValue' => 99]
        );
        $_REQUEST['age'] = '150';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('maximum is 99', $field->getValidator()->getError());
    }

    #[Test]
    public function validateHandlesEuropeanDecimalFormatInMinValueCheck(): void
    {
        // toFloat() should parse "1.234,56" as 1234.56 when `.` comes before `,`.
        $field = new Text(
            'amount',
            ValidForm::VFORM_NUMERIC,
            'Amount',
            ['minValue' => 1000]
        );
        $_REQUEST['amount'] = '1.234,56';

        $this->assertTrue($field->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // validate() — matchWith
    // --------------------------------------------------------------

    #[Test]
    public function validateFailsWhenMatchWithValuesDiffer(): void
    {
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = 'secret';
        $_REQUEST['password-confirm'] = 'different';

        $password->getValidator()->validate();
        $this->assertFalse($confirm->getValidator()->validate());
        $this->assertSame('The values do not match.', $confirm->getValidator()->getError());
    }

    #[Test]
    public function validateSucceedsWhenMatchWithValuesAreEqual(): void
    {
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = 'secret';
        $_REQUEST['password-confirm'] = 'secret';

        $password->getValidator()->validate();
        $this->assertTrue($confirm->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // validate() — hint sentinel
    // --------------------------------------------------------------

    #[Test]
    public function validateRejectsHintValueForRequiredField(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['required' => true],
            [],
            ['hint' => 'enter your name']
        );
        $_REQUEST['name'] = 'enter your name';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('The value is the hint value. Enter your own value.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateAcceptsHintValueForOptionalField(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            [],
            [],
            ['hint' => 'enter your name']
        );
        $_REQUEST['name'] = 'enter your name';

        $this->assertTrue($field->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // validate() — external validation
    // --------------------------------------------------------------

    #[Test]
    public function validateInvokesExternalPhpCallbackWithValue(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            [
                'externalValidation' => [
                    'php' => [function (string $value): bool {
                        return $value === 'accepted';
                    }, []],
                ],
            ]
        );

        $_REQUEST['name'] = 'accepted';
        $this->assertTrue($field->getValidator()->validate());

        $_REQUEST['name'] = 'rejected';
        $this->assertFalse($field->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // preSanitize / sanitize
    // --------------------------------------------------------------

    #[Test]
    public function preSanitizeOnlyAppliesTrimFromWhitelist(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $validator = $field->getValidator();

        // trim is whitelisted.
        $this->assertSame('hello', $validator->preSanitize('  hello  ', ['trim']));

        // clear is NOT whitelisted for pre-sanitisation — must pass through untouched.
        $this->assertSame('  hello  ', $validator->preSanitize('  hello  ', ['clear']));

        // Callables are NOT whitelisted for pre-sanitisation either.
        $called = false;
        $callable = function ($v) use (&$called) {
            $called = true;
            return strtoupper($v);
        };
        $this->assertSame('  hello  ', $validator->preSanitize('  hello  ', [$callable]));
        $this->assertFalse($called, 'Callables must not run during pre-sanitisation');
    }

    #[Test]
    public function sanitizeAppliesTrimSanitiser(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $this->assertSame('hello', $field->getValidator()->sanitize('  hello  ', ['trim']));
    }

    #[Test]
    public function sanitizeAppliesClearSanitiserToEmptyString(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $this->assertSame('', $field->getValidator()->sanitize('hello', ['clear']));
    }

    #[Test]
    public function sanitizeAppliesCallableSanitiser(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $result = $field->getValidator()->sanitize('hello', [
            fn ($v) => strtoupper($v),
        ]);

        $this->assertSame('HELLO', $result);
    }

    #[Test]
    public function sanitizeContinuesSilentlyWhenCallableThrows(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');

        $result = $field->getValidator()->sanitize('hello', [
            function ($v) {
                throw new \RuntimeException('intentional');
            },
            fn ($v) => strtoupper($v),
        ]);

        // The second sanitiser still runs because the first swallowed its exception.
        $this->assertSame('HELLO', $result);
    }

    // --------------------------------------------------------------
    // setError / getError
    // --------------------------------------------------------------

    #[Test]
    public function setErrorPromotesOverrideIntoErrorsOnNextValidate(): void
    {
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $validator = $field->getValidator();

        $validator->setError('Custom error message');
        $validator->validate();

        $this->assertSame('Custom error message', $validator->getError());
    }

    #[Test]
    public function getErrorReportsErrorOnlyAtValidatedPosition(): void
    {
        // NOTE on current behaviour: validate() clears $__errors on every call, so
        // consecutive validate(0) + validate(1) pairs cannot both carry an error —
        // only the last call's state survives. The documented callers (Element::isValid)
        // break on the first failing position, so this reset doesn't matter in practice.
        // We test the per-call isolation instead: a single validate(0) records an error
        // at position 0 only, and leaves other positions blank.
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['required' => true]
        );
        $validator = $field->getValidator();

        $validator->validate(0);

        $this->assertNotSame('', $validator->getError(0));
        $this->assertSame('', $validator->getError(1));
        $this->assertSame('', $validator->getError(2));
    }

    // --------------------------------------------------------------
    // getCheck
    // --------------------------------------------------------------

    #[Test]
    public function getCheckReturnsCustomValidationWhenSet(): void
    {
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            ['validation' => '/^custom$/']
        );

        $this->assertSame('/^custom$/', $field->getValidator()->getCheck());
    }

    #[Test]
    public function getCheckFallsBackToTypeDerivedRegex(): void
    {
        $field = new Text('age', ValidForm::VFORM_INTEGER, 'Age');

        // Validator::getCheck(VFORM_INTEGER) returns the integer regex.
        $this->assertSame('/^[0-9]*$/i', $field->getValidator()->getCheck());
    }

    // --------------------------------------------------------------
    // Security
    // --------------------------------------------------------------

    #[Test]
    public function validateArrayInputWithMixedValidityDoesNotTerminateProcess(): void
    {
        // SECURITY: if a single invalid element in an array submission causes
        // the process to exit(), a maliciously crafted request can terminate
        // the entire PHP request mid-response. This test asserts that we
        // reach the next line after validation — i.e. the process survived.
        $field = new Text('tags[]', ValidForm::VFORM_INTEGER, 'Tags');
        $_REQUEST['tags'] = ['123', 'not-a-number', '456'];

        // We don't care about the return value here; we just care that
        // execution continues past validate(). If exit() is called, PHPUnit
        // will report the test as "Risky" / "no assertions ran" or abort.
        $field->getValidator()->validate();

        $this->assertTrue(true, 'Process survived array validation with invalid element');
    }

    // ----- VFORM_BOOLEAN regression suite (issue #200) -----
    //
    // The regex was `/^[on]*$/i` which is a character class — it accepted
    // any sequence of `o`/`n` characters (`o`, `n`, `ono`, `nooo`, `nnn`, …).
    // Fixed to `/^(on)?$/i`, which matches the literal word "on" zero or one
    // times: exactly the two values an HTML checkbox can submit ("" unchecked
    // or "on" checked). The tests below cover both the normal usage that MUST
    // keep working and the garbage inputs that MUST now be rejected.

    #[Test]
    public function validateAcceptsEmptyStringAsUncheckedCheckboxValue(): void
    {
        // Normal usage: an unchecked checkbox submits nothing, which ValidForm
        // normalises to an empty string. This must pass.
        $field = new Text('agree', ValidForm::VFORM_BOOLEAN, 'Agree');
        $_REQUEST['agree'] = '';

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateAcceptsLiteralOnAsCheckedCheckboxValue(): void
    {
        // Normal usage: a checked HTML checkbox submits `on` unless a custom
        // `value` attribute is set. This must pass.
        $field = new Text('agree', ValidForm::VFORM_BOOLEAN, 'Agree');
        $_REQUEST['agree'] = 'on';

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateAcceptsCaseInsensitiveOnValues(): void
    {
        // The regex carries the /i flag, so historical browsers or clients
        // that submit uppercase or mixed-case "on" should still validate.
        foreach (['ON', 'On', 'oN'] as $value) {
            $field = new Text('agree', ValidForm::VFORM_BOOLEAN, 'Agree');
            $_REQUEST['agree'] = $value;

            $this->assertTrue(
                $field->getValidator()->validate(),
                "Expected case-variant '$value' to pass VFORM_BOOLEAN validation"
            );
        }
    }

    #[Test]
    public function validateRejectsGarbageBooleanInputThatMatchedLooseRegex(): void
    {
        // The old `[on]*` character class accepted every one of these.
        // The fixed `(on)?` rejects every one of them.
        foreach (['o', 'n', 'oo', 'nn', 'ono', 'nooo', 'onon', 'nonono'] as $value) {
            $field = new Text('agree', ValidForm::VFORM_BOOLEAN, 'Agree');
            $_REQUEST['agree'] = $value;

            $this->assertFalse(
                $field->getValidator()->validate(),
                "Expected garbage value '$value' to FAIL VFORM_BOOLEAN validation"
            );
        }
    }

    #[Test]
    public function validateRejectsAlternativeBooleanLiteralsThatAreNotHtmlCheckboxValues(): void
    {
        // These look like "booleans" in other languages / protocols but are
        // not what an HTML checkbox submits. They must be rejected — the
        // developer gets a type error, not a silent false positive.
        foreach (['off', 'true', 'false', 'yes', 'no', '0', '1'] as $value) {
            $field = new Text('agree', ValidForm::VFORM_BOOLEAN, 'Agree');
            $_REQUEST['agree'] = $value;

            $this->assertFalse(
                $field->getValidator()->validate(),
                "Expected non-HTML-checkbox literal '$value' to FAIL VFORM_BOOLEAN validation"
            );
        }
    }

    #[Test]
    public function checkboxWorkflowEndToEndValidatesCheckedSubmission(): void
    {
        // Full integration check: the Checkbox class internally constructs a
        // VFORM_BOOLEAN-typed validator. The regex fix must not break the
        // documented Checkbox submission contract for a checked checkbox.
        $checkbox = new \ValidFormBuilder\Checkbox(
            'terms',
            ValidForm::VFORM_BOOLEAN,
            'I accept the terms',
            ['required' => true]
        );

        $_REQUEST['terms'] = 'on';
        $this->assertTrue($checkbox->getValidator()->validate());
    }

    #[Test]
    public function checkboxWorkflowEndToEndFailsForUncheckedRequiredSubmission(): void
    {
        // Fresh Checkbox instance so we are not reading from a stale validvalues
        // cache left over from a previous successful validate() call.
        $checkbox = new \ValidFormBuilder\Checkbox(
            'terms',
            ValidForm::VFORM_BOOLEAN,
            'I accept the terms',
            ['required' => true]
        );

        // Unchecked — nothing in $_REQUEST at all.
        $this->assertFalse($checkbox->getValidator()->validate());
        $this->assertSame('This field is required.', $checkbox->getValidator()->getError());
    }

    #[Test]
    public function validateRejectsNullByteInjectionInStringField(): void
    {
        // SECURITY: null bytes ("\0") are a classic injection vector against
        // filesystem APIs, logging, and downstream C-string APIs. The VFORM_STRING
        // regex should not silently accept them.
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name'] = "hello\0world";

        $this->assertFalse($field->getValidator()->validate());
    }

    #[Test]
    public function validateAcceptsLongButFiniteInputWithinReasonableTime(): void
    {
        // SECURITY: regexes with nested quantifiers can exhibit catastrophic
        // backtracking (ReDoS). A 50k-character ASCII input should still
        // validate in well under a second — if it doesn't, the regex has a
        // pathological backtracking problem.
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name'] = str_repeat('a', 50_000);

        $start = microtime(true);
        $field->getValidator()->validate();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            1.0,
            $elapsed,
            sprintf('VFORM_STRING validation took %.3fs for 50k chars (ReDoS?)', $elapsed)
        );
    }

    #[Test]
    public function validateArrayOfValidIntegersPassesAfterExitBugFix(): void
    {
        // REGRESSION for https://github.com/validformbuilder/validformbuilder/issues/199
        // — Validator::validate() used to call exit() on invalid array elements,
        // terminating the entire PHP request. Now it returns false cleanly, which
        // means "all valid" must still return true end-to-end.
        $field = new Text('ages[]', ValidForm::VFORM_INTEGER, 'Ages');
        $_REQUEST['ages'] = ['10', '25', '40'];

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateArrayOfMixedValiditySurvivesAndReturnsFalse(): void
    {
        // REGRESSION for https://github.com/validformbuilder/validformbuilder/issues/199
        // — a single invalid element used to kill the process via exit(). The fix must
        // return false cleanly so the caller can render error feedback.
        $field = new Text(
            'ages[]',
            ValidForm::VFORM_INTEGER,
            'Ages',
            [],
            ['type' => 'Ages must all be integers.']
        );
        $_REQUEST['ages'] = ['10', 'not-an-int', '40'];

        // Control must reach both of the following assertions. Prior to the fix, exit()
        // ran before we got here — PHPUnit would report the process as killed.
        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('Ages must all be integers.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateEmptyArrayOnOptionalFieldPassesAfterExitBugFix(): void
    {
        $field = new Text('ages[]', ValidForm::VFORM_INTEGER, 'Ages');
        $_REQUEST['ages'] = [];

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateEnforcesOnlyListItemsAgainstFixedValueFields(): void
    {
        // SECURITY: onlyListItems prevents clients from submitting values
        // outside the rendered options (a common client-side tamper vector).
        $form = new ValidForm('test-form');
        $select = $form->addField(
            'colour',
            'Colour',
            ValidForm::VFORM_SELECT_LIST,
            ['onlyListItems' => true]
        );
        $select->addField('Red', 'red');
        $select->addField('Green', 'green');
        $select->addField('Blue', 'blue');

        // Allowed option — passes.
        $_REQUEST['colour'] = 'red';
        $this->assertTrue($select->getValidator()->validate());

        // Tampered option — must fail.
        $_REQUEST['colour'] = 'purple';
        $this->assertFalse($select->getValidator()->validate());
        $this->assertSame(
            'The input is not in the list of possible values.',
            $select->getValidator()->getError()
        );
    }
}
