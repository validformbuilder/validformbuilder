<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Button;

class ButtonTest extends TestCase
{
    protected Button $button;

    protected function setUp(): void
    {
        // Create a default button instance to use in tests
        $this->button = new Button("Test Button");
    }

    #[Test]
    public function getLabel(): void
    {
        $this->assertSame("Test Button", $this->button->getLabel());
    }

    #[Test]
    public function defaultTypeIsSubmit(): void
    {
        $this->assertSame("submit", $this->button->getType());
    }

    #[Test]
    public function getType(): void
    {
        // Use a button with a custom type
        $customButton = new Button("Test Button", [
            "type" => "button"
        ]);
        $this->assertSame("button", $customButton->getType());

        $html = $customButton->toHtml();
        $this->assertStringContainsString('type="button"', $html);
    }

    #[Test]
    public function setId(): void
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

    #[Test]
    public function getId(): void
    {
        $this->assertNotEmpty($this->button->getId());
    }

    #[Test]
    public function setLabel(): void
    {
        $newLabel = "New Label";
        $this->button->setLabel($newLabel);
        $this->assertSame($newLabel, $this->button->getLabel());
    }

    #[Test]
    public function setType(): void
    {
        $this->button->setType("button");
        $this->assertSame("button", $this->button->getType());
    }

    #[Test]
    public function isValid(): void
    {
        $this->assertTrue($this->button->isValid());
    }

    #[Test]
    public function isDynamic(): void
    {
        $this->assertFalse($this->button->isDynamic());
    }

    #[Test]
    public function hasFields(): void
    {
        $this->assertFalse($this->button->hasFields());
    }

    #[Test]
    public function toHtml(): void
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
