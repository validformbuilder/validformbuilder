<?php

namespace ValidFormBuilder\Tests\ValidFormBuilder;

use ValidFormBuilder\Button;
use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
    protected $button;

    public function testGetLabel(): void
    {
        $button = new Button("Test Button");
        $this->assertSame("Test Button", $button->getLabel());
    }

    public function testDefaultTypeIsSubmit(): void
    {
        $button = new Button("Test Button");
        $this->assertSame("submit", $button->getType());
    }

    public function testGetType(): void
    {
        $button = new Button("Test Button", [
            "type" => "button"
        ]);
        $this->assertSame("button", $button->getType());
    }

    public function testSetId(): void
    {

    }

    public function test__construct(): void
    {

    }

    public function testGetId(): void
    {

    }

    public function testSetLabel(): void
    {

    }

    public function testSetType(): void
    {

    }

    public function testIsValid(): void
    {

    }

    public function testIsDynamic(): void
    {

    }

    public function testHasFields(): void
    {

    }

    public function testToHtml(): void
    {

    }
}
