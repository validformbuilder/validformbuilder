<?php

namespace ValidFormBuilder;

use PHPUnit\Framework\TestCase;

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

    public function testConstructorStoresSubjectComparisonAndValue(): void
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

    public function testConstructorAllowsNullValueForEmptyComparison(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EMPTY);

        $this->assertNull($comparison->getValue());
    }

    public function testConstructorAllowsNullValueForNotEmptyComparison(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertNull($comparison->getValue());
    }

    public function testConstructorThrowsWhenValueIsNullForEqualComparison(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL);
    }

    public function testConstructorThrowsWhenValueIsNullForContainsComparison(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS);
    }

    public function testConstructorThrowsWhenEmptyComparisonAppliedToRequiredField(): void
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

    public function testConstructorAllowsNotEmptyComparisonOnRequiredField(): void
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

    public function testCheckThrowsWhenSubjectIsNotElement(): void
    {
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        // Swap the subject for a non-Element object via ClassDynamic's magic setter.
        $comparison->setSubject(new \stdClass());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Invalid subject supplied in Comparison/');

        $comparison->check();
    }

    public function testCheckReturnsFalseWhenSubjectValueIsNull(): void
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

    public function testEqualComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'hello');

        $this->assertTrue($comparison->check());
    }

    public function testEqualComparisonDoesNotMatchDifferentValue(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'world');

        $this->assertFalse($comparison->check());
    }

    public function testEqualComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'HelLo';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_EQUAL, 'hello');

        $this->assertTrue($comparison->check());
    }

    public function testNotEqualComparisonMatchesWhenDifferent(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EQUAL, 'world');

        $this->assertTrue($comparison->check());
    }

    public function testNotEqualComparisonDoesNotMatchWhenEqual(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EQUAL, 'hello');

        $this->assertFalse($comparison->check());
    }

    public function testLessThanComparisonTrueWhenValueIsSmaller(): void
    {
        $_REQUEST['numeric-field'] = '5';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN, 10);

        $this->assertTrue($comparison->check());
    }

    public function testLessThanComparisonFalseWhenValueIsLarger(): void
    {
        $_REQUEST['numeric-field'] = '15';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN, 10);

        $this->assertFalse($comparison->check());
    }

    public function testGreaterThanComparisonTrueWhenValueIsLarger(): void
    {
        $_REQUEST['numeric-field'] = '15';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN, 10);

        $this->assertTrue($comparison->check());
    }

    public function testGreaterThanComparisonFalseWhenValueIsSmaller(): void
    {
        $_REQUEST['numeric-field'] = '5';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN, 10);

        $this->assertFalse($comparison->check());
    }

    public function testLessThanOrEqualComparisonTrueOnEquality(): void
    {
        $_REQUEST['numeric-field'] = '10';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN_OR_EQUAL, 10);

        $this->assertTrue($comparison->check());
    }

    public function testLessThanOrEqualComparisonFalseWhenAbove(): void
    {
        $_REQUEST['numeric-field'] = '11';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_LESS_THAN_OR_EQUAL, 10);

        $this->assertFalse($comparison->check());
    }

    public function testGreaterThanOrEqualComparisonTrueOnEquality(): void
    {
        $_REQUEST['numeric-field'] = '10';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN_OR_EQUAL, 10);

        $this->assertTrue($comparison->check());
    }

    public function testGreaterThanOrEqualComparisonFalseWhenBelow(): void
    {
        $_REQUEST['numeric-field'] = '9';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_GREATER_THAN_OR_EQUAL, 10);

        $this->assertFalse($comparison->check());
    }

    public function testNotEmptyComparisonTrueForNonEmptyString(): void
    {
        $_REQUEST['text-field'] = 'hello';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertTrue($comparison->check());
    }

    public function testStartsWithComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'hello');

        $this->assertTrue($comparison->check());
    }

    public function testStartsWithComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'world');

        $this->assertFalse($comparison->check());
    }

    public function testStartsWithComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_STARTS_WITH, 'hello');

        $this->assertTrue($comparison->check());
    }

    public function testEndsWithComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'world');

        $this->assertTrue($comparison->check());
    }

    public function testEndsWithComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'hello');

        $this->assertFalse($comparison->check());
    }

    public function testEndsWithComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_ENDS_WITH, 'WORLD');

        $this->assertTrue($comparison->check());
    }

    public function testContainsComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'lo wo');

        $this->assertTrue($comparison->check());
    }

    public function testContainsComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'xyz');

        $this->assertFalse($comparison->check());
    }

    public function testContainsComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_CONTAINS, 'LO WO');

        $this->assertTrue($comparison->check());
    }

    public function testDoesNotContainComparisonTrueWhenSubstringAbsent(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'xyz');

        $this->assertTrue($comparison->check());
    }

    public function testDoesNotContainComparisonFalseWhenSubstringPresent(): void
    {
        $_REQUEST['text-field'] = 'hello world';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'lo wo');

        $this->assertFalse($comparison->check());
    }

    public function testDoesNotContainComparisonIsCaseInsensitive(): void
    {
        $_REQUEST['text-field'] = 'Hello World';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_DOES_NOT_CONTAIN, 'HELLO');

        $this->assertFalse($comparison->check());
    }

    public function testRegexComparisonMatches(): void
    {
        $_REQUEST['text-field'] = 'abc123';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_REGEX, '/^[a-z]+[0-9]+$/');

        $this->assertTrue($comparison->check());
    }

    public function testRegexComparisonDoesNotMatch(): void
    {
        $_REQUEST['text-field'] = 'ABC123';
        $comparison = new Comparison($this->textField, ValidForm::VFORM_COMPARISON_REGEX, '/^[a-z]+[0-9]+$/');

        $this->assertFalse($comparison->check());
    }

    public function testInArrayComparisonTrueWhenValuePresent(): void
    {
        $_REQUEST['text-field'] = 'blue';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertTrue($comparison->check());
    }

    public function testInArrayComparisonFalseWhenValueAbsent(): void
    {
        $_REQUEST['text-field'] = 'yellow';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertFalse($comparison->check());
    }

    public function testNotInArrayComparisonTrueWhenValueAbsent(): void
    {
        $_REQUEST['text-field'] = 'yellow';
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_NOT_IN_ARRAY,
            ['red', 'green', 'blue']
        );

        $this->assertTrue($comparison->check());
    }

    public function testNotInArrayComparisonFalseWhenValuePresent(): void
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

    public function testCheckHonoursDynamicPosition(): void
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

    public function testJsonSerializeReturnsStructuredArrayForElement(): void
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

    public function testJsonSerializeAppendsDynamicPositionToElementSubject(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $data = $comparison->jsonSerialize(2);

        $this->assertSame('text-field_2', $data['subject']);
    }

    public function testJsonSerializeDoesNotAppendDynamicPositionZero(): void
    {
        $comparison = new Comparison(
            $this->textField,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'hello'
        );

        $data = $comparison->jsonSerialize(0);

        $this->assertSame('text-field', $data['subject']);
    }

    public function testJsonSerializeUsesGroupFieldIdAsSubject(): void
    {
        $radio = $this->form->addField('color', 'Color', ValidForm::VFORM_RADIO_LIST);
        $redOption = $radio->addField('Red', 'red');

        $comparison = new Comparison(
            $redOption,
            ValidForm::VFORM_COMPARISON_EQUAL,
            'red'
        );

        $data = $comparison->jsonSerialize(5);

        // GroupField branch uses getId() and ignores dynamic position.
        $this->assertSame($redOption->getId(), $data['subject']);
        $this->assertStringNotContainsString('_5', $data['subject']);
    }

    // --------------------------------------------------------------
    // check() smoke tests for other element types
    // --------------------------------------------------------------

    public function testCheckWorksWithEmailField(): void
    {
        $_REQUEST['email-field'] = 'user@example.com';
        $comparison = new Comparison($this->emailField, ValidForm::VFORM_COMPARISON_NOT_EMPTY);

        $this->assertTrue($comparison->check());
    }

    public function testCheckWorksWithNumericField(): void
    {
        $_REQUEST['numeric-field'] = '42';
        $comparison = new Comparison($this->numericField, ValidForm::VFORM_COMPARISON_EQUAL, '42');

        $this->assertTrue($comparison->check());
    }
}
