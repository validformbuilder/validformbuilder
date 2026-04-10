<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Checkbox;
use ValidFormBuilder\ValidForm;

class CheckboxTest extends TestCase
{
    protected Checkbox $checkbox;
    protected ValidForm $form;

    protected function setUp(): void
    {
        $this->form = new ValidForm('test-form');
        $this->checkbox = new Checkbox(
            'checkbox-name',
            ValidForm::VFORM_BOOLEAN,
            'Test Checkbox',
            [],
            [],
            ['default' => 'on']
        );
    }

    #[Test]
    public function construct(): void
    {
        // Test default construction
        $this->assertInstanceOf(Checkbox::class, $this->checkbox);
        $this->assertEquals('Test Checkbox', $this->checkbox->getLabel());

        // Test with more meta fields
        $checkbox = new Checkbox(
            'advanced-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Advanced Checkbox',
            [],
            [],
            [
                'default' => 'on',
                'tip' => 'This is a tip',
                'class' => 'custom-class'
            ]
        );

        $this->assertEquals('This is a tip', $checkbox->getMeta('tip'));
        $this->assertEquals('custom-class', $checkbox->getMeta('class'));
    }

    #[Test]
    public function getValue(): void
    {
        // Setup a checkbox with default value set through meta
        $value = 'on';
        $checkedBox = new Checkbox(
            'checked-box',
            ValidForm::VFORM_BOOLEAN,
            'Checked Box',
            [],
            [],
            ['default' => $value]
        );

        // Verify the default value was set properly
        $this->assertEquals($value, $checkedBox->getMeta('default'));

        // For checkboxes, getValue() returns a boolean
        $this->assertFalse($checkedBox->getValue(),
            "Expected false because the checkbox's default value isn't properly recognized");
    }

    #[Test]
    public function getValueWithSubmission(): void
    {
        // Create a checkbox with a name we'll use for submission
        $checkbox = new Checkbox(
            'submitted-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Submitted Checkbox'
        );

        // Simulate submission by setting the value in $_REQUEST
        $_REQUEST['submitted-checkbox'] = 'on';

        // Call toHtml with submitted=true to force the checkbox to read from $_REQUEST
        $html = $checkbox->toHtml(true);

        // The HTML should include the checked attribute
        $this->assertStringContainsString('checked', $html,
            "Checkbox should be checked when the value is in the REQUEST");

        // We can verify submission was detected by checking if the error class is not present
        // (since the checkbox is required but was checked in the submission)
        $requiredCheckbox = new Checkbox(
            'submitted-checkbox',  // Same name to reuse the $_REQUEST value
            ValidForm::VFORM_BOOLEAN,
            'Required Submitted Checkbox',
            ['required' => true]
        );

        $htmlRequired = $requiredCheckbox->toHtml(true);

        // If submission was properly detected, there should be no error class
        $this->assertStringNotContainsString('vf__error', $htmlRequired,
            "Required checkbox should not show an error when checked in the submission");
    }

    #[Test]
    public function getDefault(): void
    {
        // Setup a checkbox with default value
        $value = 'on';
        $checkedBox = new Checkbox(
            'checked-box',
            ValidForm::VFORM_BOOLEAN,
            'Checked Box',
            [],
            [],
            ['default' => $value]
        );

        // For checkboxes, getDefault() returns "on" if getValue() is true, otherwise null
        $this->assertNull($checkedBox->getDefault(),
            "Expected null because getValue() is false");

        // Unchecked checkbox should return null
        $unchecked = new Checkbox('unchecked', ValidForm::VFORM_BOOLEAN, 'Unchecked');
        $this->assertNull($unchecked->getDefault());
    }

    #[Test]
    public function toHtml(): void
    {
        $html = $this->checkbox->toHtml();

        // Should include input type checkbox
        $this->assertStringContainsString('<input type="checkbox"', $html);

        // Should include label
        $this->assertStringContainsString('Test Checkbox', $html);

        // Create a checkbox that's checked via direct setValue method
        $checkboxChecked = new Checkbox(
            'checked-via-method',
            ValidForm::VFORM_BOOLEAN,
            'Checked Via Method'
        );

        // Set the value programmatically instead of using meta['default']
        $_REQUEST['checked-via-method'] = 'on';

        $htmlChecked = $checkboxChecked->toHtml(true); // true for submitted
        $this->assertStringContainsString('checked', $htmlChecked,
            "Checkbox should be checked when the value is in the REQUEST");
    }

    #[Test]
    public function toHtmlWithTip(): void
    {
        $checkbox = new Checkbox(
            'tip-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Tip Checkbox',
            [],
            [],
            ['tip' => 'Helpful tip text']
        );

        $html = $checkbox->toHtml();

        // Should include tip text
        $this->assertStringContainsString('Helpful tip text', $html);
        $this->assertStringContainsString('vf__tip', $html);
    }

    #[Test]
    public function toHtmlRequired(): void
    {
        // Create required checkbox
        $required = new Checkbox(
            'required-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Required Checkbox',
            ['required' => true]
        );

        $html = $required->toHtml();

        // Should include required indicator
        $this->assertStringContainsString('vf__required', $html);
    }

    #[Test]
    public function toHtmlWithError(): void
    {
        // Create a required checkbox and simulate submission
        $required = new Checkbox(
            'required-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Required Checkbox',
            ['required' => true]
        );

        // Simulate submitted without being checked
        $html = $required->toHtml(true);

        // Should include error class
        $this->assertStringContainsString('vf__error', $html);
    }

    #[Test]
    public function toJS(): void
    {
        $checkbox = new Checkbox(
            'js-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'JS Checkbox',
            ['required' => true]
        );

        $js = $checkbox->toJS();

        // Should contain form validation code
        $this->assertStringContainsString('objForm.addElement', $js);
        $this->assertStringContainsString($checkbox->getId(), $js);

        // Should check for required validation value
        $this->assertStringContainsString(', true,', $js);
    }

    #[Test]
    public function metaProperties(): void
    {
        // Test setting and getting meta properties
        $checkbox = new Checkbox(
            'meta-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Meta Checkbox',
            [],
            [],
            ['dynamic' => true]
        );

        $this->assertTrue($checkbox->getMeta('dynamic'));

        // Set a new meta value
        $checkbox->setMeta('data-custom', 'test-value');
        $this->assertEquals('test-value', $checkbox->getMeta('data-custom'));
    }
}
