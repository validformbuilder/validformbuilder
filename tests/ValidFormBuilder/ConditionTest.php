<?php

namespace ValidFormBuilder\Tests;

use ValidFormBuilder\Comparison;
use ValidFormBuilder\Condition;
use ValidFormBuilder\Element;
use ValidFormBuilder\ValidForm;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    protected ValidForm $form;
    protected Element $textField;
    protected Element $emailField;
    protected Element $numericField;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');

        // Create several field types for testing
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
        // Clean up any request variables we might have set
        if (isset($_REQUEST['text-field'])) {
            unset($_REQUEST['text-field']);
        }

        if (isset($_REQUEST['email-field'])) {
            unset($_REQUEST['email-field']);
        }

        if (isset($_REQUEST['numeric-field'])) {
            unset($_REQUEST['numeric-field']);
        }

        if (isset($_REQUEST['email-field_1'])) {
            unset($_REQUEST['email-field_1']);
        }

        if (isset($_REQUEST['email-field_2'])) {
            unset($_REQUEST['email-field_2']);
        }
    }

    public function testConditionConstruct(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true,
            ValidForm::VFORM_MATCH_ANY
        );

        $this->assertInstanceOf(Condition::class, $condition);

        $data = $condition->jsonSerialize();
        $this->assertEquals($this->textField->getName(), $data['subject']);
        $this->assertEquals('visible', $data['property']);
        $this->assertTrue($data['value']);
        $this->assertEquals(ValidForm::VFORM_MATCH_ANY, $data['comparisonType']);
    }

    public function testInvalidConditionProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Condition(
            $this->textField,
            'invalid',
            true
        );
    }

    public function testAddComparisonToCondition(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true
        );

        // Add a comparison as object
        $comparison = new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        );

        $condition->addComparison($comparison);

        // Also test adding comparison as array
        $comparisonArray = [
            $this->numericField,
            ValidForm::VFORM_COMPARISON_GREATER_THAN,
            10
        ];

        $condition->addComparison($comparisonArray);

        // Verify through jsonSerialize
        $data = $condition->jsonSerialize();
        $this->assertCount(2, $data['comparisons']);
        $this->assertEquals($this->emailField->getName(), $data['comparisons'][0]['subject']);
        $this->assertEquals($this->numericField->getName(), $data['comparisons'][1]['subject']);
    }

    public function testAddInvalidComparison(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true
        );

        $this->expectException(\InvalidArgumentException::class);

        $condition->addComparison("not a valid comparison");
    }

    public function testIsMetWithMatchAny(): void
    {
        // Set up request data
        $_REQUEST['email-field'] = 'test@example.com';
        $_REQUEST['numeric-field'] = '5'; // This is less than 10

        $condition = new Condition(
            $this->textField,
            'visible',
            true,
            ValidForm::VFORM_MATCH_ANY
        );

        // Add a comparison that should match (email not empty)
        $condition->addComparison(new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        ));

        // Add a comparison that should not match (numeric > 10)
        $condition->addComparison(new Comparison(
            $this->numericField,
            ValidForm::VFORM_COMPARISON_GREATER_THAN,
            10
        ));

        // With MATCH_ANY, one matching comparison is enough
        $this->assertTrue($condition->isMet());
    }

    public function testIsMetWithMatchAll(): void
    {
        // Set up request data
        $_REQUEST['email-field'] = 'test@example.com';
        $_REQUEST['numeric-field'] = '5'; // This is less than 10

        $condition = new Condition(
            $this->textField,
            'visible',
            true,
            ValidForm::VFORM_MATCH_ALL
        );

        // Add a comparison that should match (email not empty)
        $condition->addComparison(new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        ));

        // Add a comparison that should not match (numeric > 10)
        $condition->addComparison(new Comparison(
            $this->numericField,
            ValidForm::VFORM_COMPARISON_GREATER_THAN,
            10
        ));

        // With MATCH_ALL, all comparisons must match
        $this->assertFalse($condition->isMet());

        // Change numeric value to make both comparisons match
        $_REQUEST['numeric-field'] = '15';
        $this->assertTrue($condition->isMet());
    }

    public function testGetters(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true
        );

        $this->assertSame($this->textField, $condition->getSubject());
        $this->assertEquals('visible', $condition->getProperty());
        $this->assertTrue($condition->getValue());
    }

    public function testGetComparisons(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true
        );

        $comparison = new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        );

        $condition->addComparison($comparison);

        $comparisons = $condition->getComparisons();
        $this->assertCount(1, $comparisons);
        $this->assertSame($comparison, $comparisons[0]);
    }

    public function testGetComparisonType(): void
    {
        $condition = new Condition(
            $this->textField,
            'visible',
            true,
            ValidForm::VFORM_MATCH_ALL
        );

        $this->assertEquals(ValidForm::VFORM_MATCH_ALL, $condition->getComparisonType());
    }

    public function testIsMetWithDynamicPosition(): void
    {
        // Set up request data for dynamic fields
        $_REQUEST['email-field_1'] = 'test@example.com';
        $_REQUEST['email-field_2'] = '';

        $condition = new Condition(
            $this->textField,
            'visible',
            true
        );

        $condition->addComparison(new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        ));

        // With position 1, the comparison should be met
        $this->assertTrue($condition->isMet(1));

        // With position 2, the comparison should not be met
        $this->assertFalse($condition->isMet(2));
    }

    public function testJsonSerialize(): array
    {
        $condition = new Condition(
            $this->textField,
            'enabled',
            false,
            ValidForm::VFORM_MATCH_ALL
        );

        $condition->addComparison(new Comparison(
            $this->emailField,
            ValidForm::VFORM_COMPARISON_NOT_EMPTY
        ));

        $result = $condition->jsonSerialize();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('property', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('comparisonType', $result);
        $this->assertArrayHasKey('comparisons', $result);

        return $result;
    }

    #[Depends('testJsonSerialize')]
    public function testJsonSerializeStructure(array $result): void
    {
        $this->assertEquals('text-field', $result['subject']);
        $this->assertEquals('enabled', $result['property']);
        $this->assertEquals(false, $result['value']);
        $this->assertEquals(ValidForm::VFORM_MATCH_ALL, $result['comparisonType']);
        $this->assertCount(1, $result['comparisons']);
    }
}
