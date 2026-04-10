<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Checkbox;
use ValidFormBuilder\ValidForm;

class CheckboxTest extends TestCase
{
    use HtmlAssertionsTrait;

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
    public function submittedCheckboxRendersCheckedAttributeOnInput(): void
    {
        $checkbox = new Checkbox(
            'submitted-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Submitted Checkbox'
        );

        // Simulate submission by setting the value in $_REQUEST.
        $_REQUEST['submitted-checkbox'] = 'on';

        $xpath = $this->parseHtml($checkbox->toHtml(true));
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('checked', $input->getAttribute('checked'));

        unset($_REQUEST['submitted-checkbox']);
    }

    #[Test]
    public function requiredCheckboxSubmittedCheckedDoesNotHaveErrorClassOnWrapper(): void
    {
        $_REQUEST['submitted-checkbox'] = 'on';

        $requiredCheckbox = new Checkbox(
            'submitted-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Required Submitted Checkbox',
            ['required' => true]
        );

        $xpath = $this->parseHtml($requiredCheckbox->toHtml(true));
        $wrapper = $xpath->query('//div')->item(0);

        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotContains('vf__error', $classTokens);

        unset($_REQUEST['submitted-checkbox']);
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
    public function toHtmlRendersCheckboxInputWithLabelText(): void
    {
        $xpath = $this->parseHtml($this->checkbox->toHtml());

        $input = $xpath->query('//input[@type="checkbox"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('checkbox-name', $input->getAttribute('name'));

        $label = $xpath->query('//label[@for="checkbox-name"]')->item(0);
        $this->assertNotNull($label);
        $this->assertSame('Test Checkbox', trim($label->textContent));
    }

    #[Test]
    public function toHtmlReflectsSubmittedValueAsCheckedAttribute(): void
    {
        $checkboxChecked = new Checkbox(
            'checked-via-method',
            ValidForm::VFORM_BOOLEAN,
            'Checked Via Method'
        );

        $_REQUEST['checked-via-method'] = 'on';

        $xpath = $this->parseHtml($checkboxChecked->toHtml(true));
        $input = $xpath->query('//input[@type="checkbox"]')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('checked', $input->getAttribute('checked'));

        unset($_REQUEST['checked-via-method']);
    }

    #[Test]
    public function toHtmlWithTipAppendsSmallTipElement(): void
    {
        $checkbox = new Checkbox(
            'tip-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Tip Checkbox',
            [],
            [],
            ['tip' => 'Helpful tip text']
        );

        $xpath = $this->parseHtml($checkbox->toHtml());
        $tip = $xpath->query('//small[contains(concat(" ", normalize-space(@class), " "), " vf__tip ")]')->item(0);

        $this->assertNotNull($tip);
        $this->assertSame('Helpful tip text', trim($tip->textContent));
    }

    #[Test]
    public function toHtmlRequiredWrapsCheckboxInRequiredDiv(): void
    {
        $required = new Checkbox(
            'required-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Required Checkbox',
            ['required' => true]
        );

        $xpath = $this->parseHtml($required->toHtml());
        $wrapper = $xpath->query('//div')->item(0);

        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__required', $classTokens);
    }

    #[Test]
    public function toHtmlRequiredSubmittedEmptyRendersErrorClassAndMessage(): void
    {
        $required = new Checkbox(
            'required-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'Required Checkbox',
            ['required' => true]
        );

        $xpath = $this->parseHtml($required->toHtml(true));
        $wrapper = $xpath->query('//div')->item(0);

        $this->assertNotNull($wrapper);
        $classTokens = preg_split('/\s+/', (string) $wrapper->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__error', $classTokens);

        // Error message paragraph should also be present inside the wrapper.
        $errorMessage = $xpath->query('//div/p[contains(concat(" ", normalize-space(@class), " "), " vf__error ")]')->item(0);
        $this->assertNotNull($errorMessage);
    }

    #[Test]
    public function toJsEmitsAddElementCallForCheckboxWithRequiredFlag(): void
    {
        $checkbox = new Checkbox(
            'js-checkbox',
            ValidForm::VFORM_BOOLEAN,
            'JS Checkbox',
            ['required' => true]
        );

        $js = $checkbox->toJS();

        // Exactly one objForm.addElement(...) call.
        $this->assertSame(1, substr_count($js, 'objForm.addElement'));

        // The call starts with the field id twice (name, id) and has the required
        // flag (`true`) in the fourth positional slot.
        $this->assertMatchesRegularExpression(
            "/objForm\\.addElement\\('"
                . preg_quote($checkbox->getId(), '/')
                . "',\\s*'"
                . preg_quote($checkbox->getId(), '/')
                . "',[^,]+,\\s*true,/",
            $js
        );
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
