<?php

namespace ValidFormBuilder;

use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
    protected Button $button;

    protected function setUp(): void
    {
        // Create a default button instance to use in tests
        $this->button = new Button("Test Button");
    }

    public function testGetLabel(): void
    {
        $this->assertSame("Test Button", $this->button->getLabel());
    }

    public function testDefaultTypeIsSubmit(): void
    {
        $this->assertSame("submit", $this->button->getType());
    }

    public function testGetType(): void
    {
        // Use a button with a custom type
        $customButton = new Button("Test Button", [
            "type" => "button"
        ]);
        $this->assertSame("button", $customButton->getType());

        $html = $customButton->toHtml();
        $this->assertStringContainsString('type="button"', $html);
    }

    public function testSetId(): void
    {
        $customId = "custom-id";
        $this->button->setId($customId);
        $this->assertSame($customId, $this->button->getId());
    }

    public function test__construct(): void
    {
        // Make sure it has a default generated ID
        $this->assertNotEmpty($this->button->getId());

        $html = $this->button->toHtml();
        $this->assertStringContainsString("vf__button", $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('value="Test Button"', $html);
    }

    public function testGetId(): void
    {
        $this->assertNotEmpty($this->button->getId());
    }

    public function testSetLabel(): void
    {
        $newLabel = "New Label";
        $this->button->setLabel($newLabel);
        $this->assertSame($newLabel, $this->button->getLabel());
    }

    public function testSetType(): void
    {
        $this->button->setType("button");
        $this->assertSame("button", $this->button->getType());
    }

    public function testIsValid(): void
    {
        $this->assertTrue($this->button->isValid());
    }

    public function testIsDynamic(): void
    {
        $this->assertFalse($this->button->isDynamic());
    }

    public function testHasFields(): void
    {
        $this->assertFalse($this->button->hasFields());
    }

    public function testToHtml(): void
    {
        $customButton = new Button("My Button", [
            "type" => "button"
        ]);
        $html = $customButton->toHtml();

        $this->assertStringStartsWith("<input", $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('value="My Button"', $html);
    }
}
