<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Base;
use ValidFormBuilder\Condition;

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
        $this->assertNull($this->base->getParent());
    }

    public function testSetParent(): void
    {
        $parent = new Base();
        $this->base->setParent($parent);
        $this->assertSame($parent, $this->base->getParent());
    }

    public function testSetConditions(): void
    {
        $conditions = [new Condition($this->base, "required", true)];
        $this->base->setConditions($conditions);
        $this->assertSame($conditions, $this->base->getConditions());
    }

    public function testGetTipMeta(): void
    {
        $this->assertIsArray($this->base->getTipMeta());
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
