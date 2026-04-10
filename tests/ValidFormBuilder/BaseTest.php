<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function getId(): void
    {
        // Default value is null
        $this->assertNull($this->base->getId());
    }

    #[Test]
    public function setId(): void
    {
        $this->assertNull($this->base->getId());
        $this->base->setId("test-id");
        $this->assertSame("test-id", $this->base->getId());
    }

    #[Test]
    public function getName(): void
    {
        // Assert the default name follows this pattern: base_{random number}
        $this->assertMatchesRegularExpression("/^base_[0-9]+$/", $this->base->getName());
    }

    #[Test]
    public function setName(): void
    {
        $this->base->setName("test-name");
        $this->assertSame("test-name", $this->base->getName());
    }

    #[Test]
    public function getParent(): void
    {
        // Default value should be null.
        $this->assertNull($this->base->getParent());
    }

    #[Test]
    public function setParent(): void
    {
        $area = new Area("test-area");
        $this->base->setParent($area);
        $this->assertSame($area, $this->base->getParent());
    }

    #[Test]
    public function setConditions(): void
    {
        // TODO: This method should be marked deprecated and/or removed from the Base class, as per #157
        $this->assertTrue(true);
    }

    #[Test]
    public function getTipMeta(): void
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

    #[Test]
    public function getDynamicLabelMeta(): void
    {
        $this->assertIsArray($this->base->getDynamicLabelMeta());
    }

    #[Test]
    public function getDynamicRemoveLabelMeta(): void
    {
        $this->assertIsArray($this->base->getDynamicRemoveLabelMeta());
    }

    #[Test]
    public function getMagicMeta(): void
    {
        $magicMetaList = ["label", "field", "tip", "dynamicLabel", "dynamicRemoveLabel"];
        $magicMeta = $this->base->getMagicMeta();
        $this->assertIsArray($magicMeta);

        foreach ($magicMetaList as $metaKey) {
            $this->assertContains($metaKey, $magicMeta, "Missing expected meta key: $metaKey");
        }
    }

    #[Test]
    public function getMagicReservedMeta(): void
    {
        $reservedMetaList = ["labelRange", "tip"];
        $reservedMeta = $this->base->getMagicReservedMeta();
        $this->assertIsArray($reservedMeta);

        foreach ($reservedMetaList as $metaKey) {
            $this->assertContains($metaKey, $reservedMeta, "Missing expected meta key: $metaKey");
        }
    }

    #[Test]
    public function getReservedFieldMeta(): void
    {
        $reservedMetaList = ["multiple", "rows", "cols"];
        $reservedMeta = $this->base->getReservedFieldMeta();
        $this->assertIsArray($reservedMeta);

        foreach ($reservedMetaList as $metaKey) {
            $this->assertContains($metaKey, $reservedMeta, "Missing expected meta key: $metaKey");
        }
    }

    #[Test]
    public function getReservedLabelMeta(): void
    {
        $reservedMetaList = [];
        $reservedMeta = $this->base->getReservedLabelMeta();
        $this->assertIsArray($reservedMeta);

        foreach ($reservedMetaList as $metaKey) {
            $this->assertContains($metaKey, $reservedMeta, "Missing expected meta key: $metaKey");
        }
    }

    #[Test]
    public function getReservedMeta(): void
    {
        $reservedMetaList = [
            "parent",
            "data",
            "dynamicCounter",
            "tip",
            "hint",
            "default",
            "width",
            "height",
            "length",
            "start",
            "end",
            "path",
            "labelStyle",
            "labelClass",
            "labelRange",
            "fieldStyle",
            "fieldClass",
            "tipStyle",
            "tipClass",
            "valueRange",
            "dynamic",
            "dynamicLabel",
            "dynamicLabelStyle",
            "dynamicLabelClass",
            "dynamicRemoveLabel",
            "dynamicRemoveLabelStyle",
            "dynamicRemoveLabelClass",
            "matchWith",
            "uniqueId",
            "sanitize",
            "displaySanitize"
        ];

        $reservedMeta = $this->base->getReservedMeta();
        $this->assertIsArray($reservedMeta);

        foreach ($reservedMetaList as $metaKey) {
            $this->assertContains($metaKey, $reservedMeta, "Missing expected meta key: $metaKey");
        }
    }

    #[Test]
    public function hasFields(): void
    {
        // Placeholder, these should be removed once issue #155 is accepted
        $this->assertTrue(true);
    }

    #[Test]
    public function getFields(): void
    {
        // Placeholder, these should be removed once issue #155 is accepted
        $this->assertTrue(true);
    }
}
