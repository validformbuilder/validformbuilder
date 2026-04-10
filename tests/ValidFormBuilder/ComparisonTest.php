<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Comparison;
use ValidFormBuilder\Element;
use ValidFormBuilder\ValidForm;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Comparison}.
 *
 * Surface covered:
 * - Constructor validation (null value rules, required field rule)
 * - ClassDynamic magic getters (getSubject, getComparison, getValue)
 * - check() subject-type validation and $_REQUEST wiring
 * - __verify() behavior for every VFORM_COMPARISON_* constant
 * - jsonSerialize() for Element, GroupField, and dynamic positions
 */
class ComparisonTest extends TestCase
{
    private ValidForm $form;
    private Element $textField;
    private Element $emailField;
    private Element $numericField;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');

        $this->textField = $this->form->addField(
            'text-field',
            'Text Field',
            ValidForm::VFORM_STRING
        );

        $this->emailField = $this->form->addField(
            'email-field',
            'Email Field',
            ValidForm::VFORM_EMAIL
        );

        $this->numericField = $this->form->addField(
            'numeric-field',
            'Numeric Field',
            ValidForm::VFORM_NUMERIC
        );
    }

    protected function tearDown(): void
    {
        // Clean up any $_REQUEST keys tests may have set.
        foreach (['text-field', 'email-field', 'numeric-field', 'text-field_1', 'text-field_2'] as $key) {
            unset($_REQUEST[$key]);
        }
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    #[Test]
    public function constructorStoresSubjectComparisonAndValue(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $this->assertInstanceOf(Comparison::class, $comparison);
        $this->assertSame($this->textField, $comparison->getSubject());
        $this->assertSame(ValidForm::VFORM_COMPARISON_EQUAL, $comparison->getComparison());
        $this->assertSame('hello', $comparison->getValue());
    }

    #[Test]
    public function constructorAllowsNullValueForEmptyComparison(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EMPTY);

        $this->assertNull($comparison->getValue());
    }

    #[Test]
    public function constructorAllowsNullValueForNotEmptyComparison(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertNull($comparison->getValue());
    }

    #[Test]
    public function constructorThrowsWhenValueIsNullForEqualComparison(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL);
    }

    #[Test]
    public function constructorThrowsWhenValueIsNullForContainsComparison(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS);
    }

    #[Test]
    public function constructorThrowsWhenEmptyComparisonAppliedToRequiredField(): void
    {
        $requiredField = $this->form->addField(
            'required-field',
            'Required Field',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        $this->expectException(\Exception::class);

        new Comparison($requiredField, ValidForm::VFORM_COMPARISON_EMPTY);
    }

    #[Test]
    public function constructorAllowsNotEmptyComparisonOnRequiredField(): void
    {
        $requiredField = $this->form->addField(
            'required-field-ok',
            'Required Field',
            ValidForm::VFORM_STRING,
            ['required' => true]
        );

        $comparison = new Comparison($requiredField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertInstanceOf(Comparison::class, $comparison);
    }

    // --------------------------------------------------------------
    // check() — subject validation
    // --------------------------------------------------------------

    #[Test]
    public function checkThrowsWhenSubjectIsNotElement(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        // Swap the subject for a non-Element object via ClassDynamic's magic setter.
        $comparison->setSubject(new \stdClass());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Invalid subject supplied in Comparison/');

        $comparison->check();
    }

    #[Test]
    public function checkReturnsFalseWhenSubjectValueIsNull(): void
    {
        // No $_REQUEST, no default set → field value resolves to null → check() returns false.
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'anything'
        );

        $this->assertFalse($comparison->check());
    }

    // --------------------------------------------------------------
    // __verify() — one test per comparison type, via check()
    // --------------------------------------------------------------

    #[Test]
    public function equalComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'hello');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function equalComparisonDoesNotMatchDifferentValue(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'world');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function equalComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'HelLo';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'hello');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function notEqualComparisonMatchesWhenDifferent(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EQUAL, 'world');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function notEqualComparisonDoesNotMatchWhenEqual(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EQUAL, 'hello');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function lessThanComparisonTrueWhenValueIsSmaller(): void
    {
        $_REQUEST['numeric-field'] = '5';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN, 10);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function lessThanComparisonFalseWhenValueIsLarger(): void
    {
        $_REQUEST['numeric-field'] = '15';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN, 10);

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function greaterThanComparisonTrueWhenValueIsLarger(): void
    {
        $_REQUEST['numeric-field'] = '15';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN, 10);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function greaterThanComparisonFalseWhenValueIsSmaller(): void
    {
        $_REQUEST['numeric-field'] = '5';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN, 10);

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function lessThanOrEqualComparisonTrueOnEquality(): void
    {
        $_REQUEST['numeric-field'] = '10';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN_OR_EQUAL, 10);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function lessThanOrEqualComparisonFalseWhenAbove(): void
    {
        $_REQUEST['numeric-field'] = '11';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN_OR_EQUAL, 10);

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function greaterThanOrEqualComparisonTrueOnEquality(): void
    {
        $_REQUEST['numeric-field'] = '10';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN_OR_EQUAL, 10);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function greaterThanOrEqualComparisonFalseWhenBelow(): void
    {
        $_REQUEST['numeric-field'] = '9';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN_OR_EQUAL, 10);

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function notEmptyComparisonTrueForNonEmptyString(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function startsWithComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'hello');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function startsWithComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'world');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function startsWithComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'hello');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function endsWithComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'world');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function endsWithComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'hello');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function endsWithComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'WORLD');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function containsComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'lo wo');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function containsComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'xyz');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function containsComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'LO WO');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function doesNotContainComparisonTrueWhenSubstringAbsent(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'xyz');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function doesNotContainComparisonFalseWhenSubstringPresent(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'lo wo');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function doesNotContainComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'HELLO');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function regexComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'abc123';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_REGEX, '/^[a-z]+[0-9]+$/');

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function regexComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'ABC123';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_REGEX, '/^[a-z]+[0-9]+$/');

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function inArrayComparisonTrueWhenValuePresent(): void
    {
        $_REQUEST['text-field'] = 'blue';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function inArrayComparisonFalseWhenValueAbsent(): void
    {
        $_REQUEST['text-field'] = 'yellow';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertFalse($comparison->check());
    }

    #[Test]
    public function notInArrayComparisonTrueWhenValueAbsent(): void
    {
        $_REQUEST['text-field'] = 'yellow';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_NOT_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function notInArrayComparisonFalseWhenValuePresent(): void
    {
        $_REQUEST['text-field'] = 'blue';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_NOT_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertFalse($comparison->check());
    }

    // --------------------------------------------------------------
    // Dynamic position
    // --------------------------------------------------------------

    #[Test]
    public function checkHonoursDynamicPosition(): void
    {
        // Dynamic fields are addressed as fieldname_{position} in $_REQUEST.
        $_REQUEST['text-field_1'] = 'first';
        $_REQUEST['text-field_2'] = 'second';

        $first = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'first');
        $second = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'second');

        $this->assertTrue($first->check(1));
        $this->assertFalse($first->check(2));
        $this->assertTrue($second->check(2));
        $this->assertFalse($second->check(1));
    }

    // --------------------------------------------------------------
    // jsonSerialize()
    // --------------------------------------------------------------

    #[Test]
    public function jsonSerializeReturnsStructuredArrayForElement(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $data = $comparison->jsonSerialize();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('comparison', $data);
        $this->assertArrayHasKey('value', $data);
        $this->assertSame('text-field', $data['subject']);
        $this->assertSame(ValidForm::VFORM_COMPARISON_EQUAL, $data['comparison']);
        $this->assertSame('hello', $data['value']);
    }

    #[Test]
    public function jsonSerializeAppendsDynamicPositionToElementSubject(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $data = $comparison->jsonSerialize(2);

        $this->assertSame('text-field_2', $data['subject']);
    }

    #[Test]
    public function jsonSerializeDoesNotAppendDynamicPositionZero(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $data = $comparison->jsonSerialize(0);

        $this->assertSame('text-field', $data['subject']);
    }

    #[Test]
    public function jsonSerializeUsesGroupFieldIdAsSubject(): void
    {
        $radio = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $redOption = $radio->addField('Red', 'red');

        $comparison = new Comparison(
            $redOption,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'red'
        );

        // GroupField branch uses getId() and ignores any dynamic position argument,
        // so the subject must match getId() verbatim regardless of position.
        $this->assertSame($redOption->getId(), $comparison->jsonSerialize()['subject']);
        $this->assertSame($redOption->getId(), $comparison->jsonSerialize(5)['subject']);
        $this->assertSame($redOption->getId(), $comparison->jsonSerialize(42)['subject']);
    }

    // --------------------------------------------------------------
    // check() smoke tests for other element types
    // --------------------------------------------------------------

    #[Test]
    public function checkWorksWithEmailField(): void
    {
        $_REQUEST['email-field'] = 'user@example.com';
        $comparison = new Comparison($this->emailField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertTrue($comparison->check());
    }

    #[Test]
    public function checkWorksWithNumericField(): void
    {
        $_REQUEST['numeric-field'] = '42';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_EQUAL, '42');

        $this->assertTrue($comparison->check());
    }
}
