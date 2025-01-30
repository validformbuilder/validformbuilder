<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Base;

class BaseTest extends TestCase
{
    private Base $base;

    protected function setUp(): void
    {
        $this->base = new Base();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->base->getId());
    }

    public function testSetId(): void
    {
        $this->base->setId("test-id");
        $this->assertSame("test-id", $this->base->getId());
    }

    public function testSetName(): void
    {
        $this->base->setName("test-name");
        $this->assertSame("test-name", $this->base->getName());
    }

    public function testGetParent(): void
    {
        // Default value should be null.
        $this->assertNull($this->base->getParent());
    }

    public function testSetParent(): void
    {
        $area = new Area("test-area");
        $this->base->setParent($area);
        $this->assertSame($area, $this->base->getParent());
    }

    public function testSetConditions(): void
    {
        // TODO: This method should be marked deprecated and/or removed from the Base class, as per #157
        $this->assertTrue(true);
    }

    public function testGetTipMeta(): void
    {
        $this->assertIsArray($this->base->getTipMeta());
        $this->assertEmpty($this->base->getTipMeta());

        // Now set the tip meta and check again
        $this->base->setTipMeta("Fancy property", "value");

        // Keys are converted to lower case
        $this->assertArrayNotHasKey("Fancy property", $this->base->getTipMeta());
        $this->assertArrayHasKey("fancy property", $this->base->getTipMeta());

        $this->assertSame("value", $this->base->getTipMeta()["fancy property"]);
    }

    public function testGetDynamicLabelMeta(): void
    {
        $this->assertIsArray($this->base->getDynamicLabelMeta());
    }

    public function testGetDynamicRemoveLabelMeta(): void
    {
        $this->assertIsArray($this->base->getDynamicRemoveLabelMeta());
    }

    public function testGetMagicMeta(): void
    {
        $this->assertIsArray($this->base->getMagicMeta());
    }

    public function testGetMagicReservedMeta(): void
    {
        $this->assertIsArray($this->base->getMagicReservedMeta());
    }

    public function testGetReservedFieldMeta(): void
    {
        $this->assertIsArray($this->base->getReservedFieldMeta());
    }

    public function testGetReservedLabelMeta(): void
    {
        $this->assertIsArray($this->base->getReservedLabelMeta());
    }

    public function testGetReservedMeta(): void
    {
        $this->assertIsArray($this->base->getReservedMeta());
    }

    public function testHasFields(): void
    {
        // Placeholder, these should be removed once issue #155 is accepted
        $this->assertTrue(true);
    }

    public function testGetFields(): void
    {
        // Placeholder, these should be removed once issue #155 is accepted
        $this->assertTrue(true);
    }
}
