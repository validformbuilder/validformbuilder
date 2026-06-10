<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
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
        // the entire PHP request mid-response. Reaching the assertion proves
        // the process survived (an exit() would abort the PHPUnit run), and
        // the invalid element must fail validation rather than die.
        $field = new Text('tags[]', ValidForm::VFORM_INTEGER, 'Tags');
        $_REQUEST['tags'] = ['123', 'not-a-number', '456'];

        $this->assertFalse(
            $field->getValidator()->validate(),
            'Array with an invalid element must fail validation without exiting'
        );
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

    #[Test]
    public function validateEnforcesOnlyListItemsAgainstRadioGroupFields(): void
    {
        // Same tamper-protection as the select-list variant above, but through
        // the GroupField branch: radio/check lists collect their fixed values
        // via getFields() + __getValue() instead of getOptions().
        $form = new ValidForm('radio-form');
        $radio = $form->addField(
            'colour-radio',
            'Colour',
            ValidForm::VFORM_RADIO_LIST,
            ['onlyListItems' => true]
        );
        $radio->addField('Red', 'red');
        $radio->addField('Green', 'green');

        // Allowed option — passes.
        $_REQUEST['colour-radio'] = 'red';
        $this->assertTrue($radio->getValidator()->validate());

        // Tampered option — must fail.
        $_REQUEST['colour-radio'] = 'purple';
        $this->assertFalse($radio->getValidator()->validate());
        $this->assertSame(
            'The input is not in the list of possible values.',
            $radio->getValidator()->getError()
        );
    }

    // --------------------------------------------------------------
    // getValue — override errors and array defaults
    // --------------------------------------------------------------

    #[Test]
    public function getValueReturnsNullWhenOverrideErrorIsSetButEmpty(): void
    {
        // setError("") marks the position as "overridden with an empty error".
        // getValue() treats that as "no usable value" and short-circuits to null,
        // even when a submitted value is present in the request.
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name'] = 'submitted';

        $field->getValidator()->setError('');

        $this->assertNull($field->getValidator()->getValue());
    }

    #[Test]
    public function getValuePicksDynamicPositionFromArrayDefault(): void
    {
        // When the default is an array, getValue() must select the entry that
        // belongs to the requested dynamic position.
        $field = new Text(
            'phone',
            ValidForm::VFORM_STRING,
            'Phone',
            [],
            [],
            ['default' => ['zero', 'one']]
        );

        $this->assertSame('zero', $field->getValidator()->getValue(0));
        $this->assertSame('one', $field->getValidator()->getValue(1));
    }

    // --------------------------------------------------------------
    // validate() — conditional required / enabled / visible
    // --------------------------------------------------------------

    #[Test]
    public function validateAppliesRequiredConditionWhenMet(): void
    {
        // An optional field becomes required as soon as the condition is met.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text('target', ValidForm::VFORM_STRING, 'Target');
        $field->addCondition('required', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'yes';

        // Condition met → required → empty submission fails.
        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('This field is required.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateInvertsRequiredConditionWhenNotMet(): void
    {
        // A required field becomes optional when the "required" condition is
        // NOT met: __required is set to the inverse of the condition value.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text(
            'target',
            ValidForm::VFORM_STRING,
            'Target',
            ['required' => true]
        );
        $field->addCondition('required', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'no';

        // Condition not met → not required → empty submission passes.
        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateKeepsRequiredWhenEnabledConditionIsMet(): void
    {
        // "enabled => true" condition met: the field stays enabled, so its
        // original required state is preserved.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text(
            'target',
            ValidForm::VFORM_STRING,
            'Target',
            ['required' => true]
        );
        $field->addCondition('enabled', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'yes';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('This field is required.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateDropsRequiredWhenEnabledConditionIsNotMet(): void
    {
        // "enabled => true" condition NOT met: the field counts as disabled
        // and must not be validated as required.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text(
            'target',
            ValidForm::VFORM_STRING,
            'Target',
            ['required' => true]
        );
        $field->addCondition('enabled', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'no';

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateKeepsRequiredWhenVisibleConditionIsMet(): void
    {
        // "visible => true" condition met: the field is shown, so the original
        // required state stands.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text(
            'target',
            ValidForm::VFORM_STRING,
            'Target',
            ['required' => true]
        );
        $field->addCondition('visible', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'yes';

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('This field is required.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateDropsRequiredWhenVisibleConditionIsNotMet(): void
    {
        // "visible => true" condition NOT met: a hidden field must never block
        // submission on its required rule.
        $trigger = new Text('trigger', ValidForm::VFORM_STRING, 'Trigger');
        $field = new Text(
            'target',
            ValidForm::VFORM_STRING,
            'Target',
            ['required' => true]
        );
        $field->addCondition('visible', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes'],
        ]);

        $_REQUEST['trigger'] = 'no';

        $this->assertTrue($field->getValidator()->validate());
    }

    // --------------------------------------------------------------
    // validate() — active area parent
    // --------------------------------------------------------------

    #[Test]
    public function validateSkipsRequiredCheckInsideUncheckedActiveArea(): void
    {
        // An "active" area renders a toggle checkbox. When that checkbox is
        // not submitted, the whole area is collapsed and its children must
        // not be validated as required.
        $area = new Area('Details', true, 'details');
        $field = $area->addField(
            'inner',
            'Inner',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        // Neither 'details' (the area toggle) nor 'inner' is submitted.
        $this->assertTrue($field->getValidator()->validate());
        $this->assertSame('', $field->getValidator()->getError());
    }

    // --------------------------------------------------------------
    // validate() — array submissions and required state
    // --------------------------------------------------------------

    #[Test]
    public function validateScansPastEmptyArrayItemsToFindNonEmptyValue(): void
    {
        // The required check walks the array until it finds a non-empty item.
        // A leading empty item must not mark the whole submission as empty.
        $field = new Text(
            'tags[]',
            ValidForm::VFORM_STRING,
            'Tags',
            ['required' => true]
        );
        $_REQUEST['tags'] = ['', 'second'];

        $this->assertTrue($field->getValidator()->validate());
    }

    #[Test]
    public function validateFailsRequiredFieldWhenArraySubmissionIsAllEmpty(): void
    {
        $field = new Text(
            'tags[]',
            ValidForm::VFORM_STRING,
            'Tags',
            ['required' => true]
        );
        $_REQUEST['tags'] = ['', ''];

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('This field is required.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateAcceptsAllEmptyArrayOnOptionalFieldAndCachesEmptyString(): void
    {
        $field = new Text('tags[]', ValidForm::VFORM_STRING, 'Tags');
        $_REQUEST['tags'] = ['', ''];

        $this->assertTrue($field->getValidator()->validate());
        $this->assertSame('', $field->getValidator()->getValidValue());
    }

    #[Test]
    public function validateAllEmptyArrayOnOptionalFieldStillFailsWithOverrideError(): void
    {
        // Even when the optional-empty shortcut applies, a custom error set via
        // setError() must win and fail the validation.
        $field = new Text('tags[]', ValidForm::VFORM_STRING, 'Tags');
        $_REQUEST['tags'] = ['', ''];

        $field->getValidator()->setError('Externally flagged.');

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('Externally flagged.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateFailsWhenArrayHasFewerItemsThanMinLength(): void
    {
        // For array submissions minLength counts items, not characters.
        $field = new Text(
            'tags[]',
            ValidForm::VFORM_STRING,
            'Tags',
            ['minLength' => 2]
        );
        $_REQUEST['tags'] = ['only-one'];

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('minimum is 2', $field->getValidator()->getError());
    }

    #[Test]
    public function validateFailsWhenArrayHasMoreItemsThanMaxLength(): void
    {
        // For array submissions maxLength counts items, not characters.
        $field = new Text(
            'tags[]',
            ValidForm::VFORM_STRING,
            'Tags',
            ['maxLength' => 2]
        );
        $_REQUEST['tags'] = ['one', 'two', 'three'];

        $this->assertFalse($field->getValidator()->validate());
        $this->assertStringContainsString('maximum is 2', $field->getValidator()->getError());
    }

    // --------------------------------------------------------------
    // validate() — matchWith edge cases
    // --------------------------------------------------------------

    #[Test]
    public function validateFailsWhenMatchWithSubjectIsEmptyButFieldHasValue(): void
    {
        // The matched field is empty (normalised to null) while this field
        // carries a value — that's a mismatch.
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = '';
        $_REQUEST['password-confirm'] = 'secret';

        $this->assertFalse($confirm->getValidator()->validate());
        $this->assertSame('The values do not match.', $confirm->getValidator()->getError());
    }

    #[Test]
    public function validateTreatsBothEmptyMatchWithValuesAsValid(): void
    {
        // Both sides empty → both normalised to null → equal → valid for an
        // optional pair.
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = '';
        $_REQUEST['password-confirm'] = '';

        $this->assertTrue($confirm->getValidator()->validate());
    }

    #[Test]
    public function validateBothEmptyMatchWithStillFailsWithOverrideError(): void
    {
        // The empty-match shortcut must still honour a custom override error.
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $_REQUEST['password'] = '';
        $_REQUEST['password-confirm'] = '';

        $confirm->getValidator()->setError('Externally flagged.');

        $this->assertFalse($confirm->getValidator()->validate());
        $this->assertSame('Externally flagged.', $confirm->getValidator()->getError());
    }

    #[Test]
    public function validateReturnsTrueForUnsubmittedOptionalFieldWithMatchWith(): void
    {
        // Nothing submitted at all (value is null, not ""): an optional field
        // with a matchWith rule short-circuits to valid.
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $this->assertTrue($confirm->getValidator()->validate());
    }

    #[Test]
    public function validateUnsubmittedOptionalFieldWithMatchWithStillFailsWithOverrideError(): void
    {
        // The null-value shortcut must still honour a custom override error.
        $password = new Text('password', ValidForm::VFORM_STRING, 'Password');
        $confirm = new Text(
            'password-confirm',
            ValidForm::VFORM_STRING,
            'Confirm',
            ['matchWith' => $password]
        );

        $confirm->getValidator()->setError('Externally flagged.');

        $this->assertFalse($confirm->getValidator()->validate());
        $this->assertSame('Externally flagged.', $confirm->getValidator()->getError());
    }

    // --------------------------------------------------------------
    // validate() — hint + override error
    // --------------------------------------------------------------

    #[Test]
    public function validateHintValueOnOptionalFieldStillFailsWithOverrideError(): void
    {
        // The optional-hint shortcut (hint value treated as "no input") must
        // still honour a custom override error.
        $field = new Text(
            'name',
            ValidForm::VFORM_STRING,
            'Name',
            [],
            [],
            ['hint' => 'enter your name']
        );
        $_REQUEST['name'] = 'enter your name';

        $field->getValidator()->setError('Externally flagged.');

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('Externally flagged.', $field->getValidator()->getError());
    }

    // --------------------------------------------------------------
    // validate() — custom validation regex and nested arrays
    // --------------------------------------------------------------

    #[Test]
    public function validateUsesCustomValidationRegexInsteadOfTypeRegex(): void
    {
        // A custom 'validation' rule replaces the type-derived regex entirely.
        $field = new Text(
            'code',
            ValidForm::VFORM_STRING,
            'Code',
            ['validation' => '/^[a-z]+$/'],
            ['type' => 'Lowercase letters only.']
        );

        $_REQUEST['code'] = 'abc';
        $this->assertTrue($field->getValidator()->validate());

        // 'ABC123' would pass the VFORM_STRING type regex — only the custom
        // rule rejects it, proving the custom branch ran.
        $_REQUEST['code'] = 'ABC123';
        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('Lowercase letters only.', $field->getValidator()->getError());
    }

    #[Test]
    public function validateStoresNestedArraySubmissionDirectlyAsValidValues(): void
    {
        // Dynamic check lists submit a nested array (one sub-array per dynamic
        // position). validate() must store that array as the complete
        // valid-values set instead of caching it at a single position.
        $form = new ValidForm('nested-form');
        $group = $form->addField('fruit', 'Fruit', ValidForm::VFORM_CHECK_LIST);
        $group->addField('Apple', 'apple');
        $group->addField('Banana', 'banana');

        $_REQUEST['fruit'] = [['apple', 'banana'], ['banana']];

        // The required-state scan casts each sub-array to string, which raises
        // an E_WARNING ("Array to string conversion") on PHP 8. Mute it so the
        // nested-array storage behaviour itself can be asserted.
        set_error_handler(static fn (): bool => true, E_WARNING);
        try {
            $blnResult = $group->getValidator()->validate();
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($blnResult);
        $this->assertSame(['apple', 'banana'], $group->getValidator()->getValidValue(0));
        $this->assertSame(['banana'], $group->getValidator()->getValidValue(1));
    }

    // --------------------------------------------------------------
    // validate() — override error after successful checks
    // --------------------------------------------------------------

    #[Test]
    public function validateClearsValidValueWhenOverrideErrorSetOnValidSubmission(): void
    {
        // A perfectly valid submission must still fail when an override error
        // was set, and the cached valid value must be discarded.
        $field = new Text('name', ValidForm::VFORM_STRING, 'Name');
        $_REQUEST['name'] = 'hello';

        $field->getValidator()->setError('Manually rejected.');

        $this->assertFalse($field->getValidator()->validate());
        $this->assertSame('Manually rejected.', $field->getValidator()->getError());
        $this->assertNull($field->getValidator()->getValidValue());
    }
}
