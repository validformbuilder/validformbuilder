<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Button;

class ButtonTest extends TestCase
{
    use HtmlAssertionsTrait;

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
    public function customTypeIsReflectedInInputAttribute(): void
    {
        $customButton = new Button("Test Button", ["type" => "button"]);
        $this->assertSame("button", $customButton->getType());

        $xpath = $this->parseHtml($customButton->toHtml());
        $input = $xpath->query('//input')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('button', $input->getAttribute('type'));
    }

    #[Test]
    public function setId(): void
    {
        $customId = "custom-id";
        $this->button->setId($customId);
        $this->assertSame($customId, $this->button->getId());
    }

    #[Test]
    public function defaultConstructedButtonRendersAsSubmitInputWithButtonClass(): void
    {
        // Make sure it has a default generated ID
        $this->assertNotEmpty($this->button->getId());

        $xpath = $this->parseHtml($this->button->toHtml());
        $input = $xpath->query('//input')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('submit', $input->getAttribute('type'));
        $this->assertSame('Test Button', $input->getAttribute('value'));
        $this->assertStringContainsString('vf__button', $input->getAttribute('class'));
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
    public function toHtmlRendersSingleInputWithTypeAndValue(): void
    {
        $customButton = new Button("My Button", ["type" => "button"]);

        $xpath = $this->parseHtml($customButton->toHtml());
        $inputs = $xpath->query('//input');

        $this->assertSame(1, $inputs->length);
        $this->assertSame('button', $inputs->item(0)->getAttribute('type'));
        $this->assertSame('My Button', $inputs->item(0)->getAttribute('value'));
    }
}
