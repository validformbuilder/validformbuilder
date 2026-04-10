<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Base;
use ValidFormBuilder\ValidForm;

/**
 * Coverage for {@link \ValidFormBuilder\Base}.
 *
 * Base is the parent class for all ValidForm objects. It holds the
 * id/name/parent, meta arrays (field, label, tip, dynamic-label),
 * conditions system, and custom data storage.
 */
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

    // --------------------------------------------------------------
    // isDynamic
    // --------------------------------------------------------------

    #[Test]
    public function isDynamicDefaultsToFalse(): void
    {
        $this->assertFalse($this->base->isDynamic());
    }

    // --------------------------------------------------------------
    // Meta setters / getters
    // --------------------------------------------------------------

    #[Test]
    public function setMetaAndGetMetaRoundTrip(): void
    {
        $this->base->setMeta('data-custom', 'hello');

        $this->assertSame('hello', $this->base->getMeta('data-custom'));
    }

    #[Test]
    public function getMetaReturnsFullArrayWhenPropertyIsNull(): void
    {
        $this->base->setMeta('foo', 'bar');

        $meta = $this->base->getMeta(null);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('foo', $meta);
    }

    #[Test]
    public function getMetaReturnsFallbackWhenPropertyMissing(): void
    {
        $this->assertSame('default', $this->base->getMeta('nonexistent', 'default'));
    }

    #[Test]
    public function setFieldMetaAndGetFieldMetaRoundTrip(): void
    {
        $this->base->setFieldMeta('class', 'vf__test');

        $this->assertSame('vf__test', $this->base->getFieldMeta('class'));
    }

    #[Test]
    public function getFieldMetaReturnsFullArrayWhenPropertyIsNull(): void
    {
        $this->base->setFieldMeta('class', 'vf__test');

        $fieldMeta = $this->base->getFieldMeta(null);
        $this->assertIsArray($fieldMeta);
    }

    #[Test]
    public function getFieldMetaReturnsFallbackWhenPropertyMissing(): void
    {
        $this->assertSame('fallback', $this->base->getFieldMeta('missing', 'fallback'));
    }

    #[Test]
    public function setLabelMetaStoresUnderLabelPrefix(): void
    {
        $this->base->setLabelMeta('class', 'label-class');

        $this->assertSame('label-class', $this->base->getLabelMeta('class'));
    }

    #[Test]
    public function getLabelMetaReturnsFullArrayWhenPropertyIsNull(): void
    {
        $labelMeta = $this->base->getLabelMeta(null);

        $this->assertIsArray($labelMeta);
    }

    #[Test]
    public function getLabelMetaReturnsFallbackWhenPropertyMissing(): void
    {
        $this->assertSame('fallback', $this->base->getLabelMeta('nonexistent', 'fallback'));
    }

    #[Test]
    public function setDynamicLabelMetaStoresValue(): void
    {
        $this->base->setDynamicLabelMeta('class', 'dyn-class');

        $dynMeta = $this->base->getDynamicLabelMeta();
        $this->assertArrayHasKey('class', $dynMeta);
        $this->assertSame('dyn-class', $dynMeta['class']);
    }

    #[Test]
    public function setDynamicRemoveLabelMetaStoresValue(): void
    {
        $this->base->setDynamicRemoveLabelMeta('class', 'remove-class');

        $removeMeta = $this->base->getDynamicRemoveLabelMeta();
        $this->assertArrayHasKey('class', $removeMeta);
        $this->assertSame('remove-class', $removeMeta['class']);
    }

    // --------------------------------------------------------------
    // setData / getData
    // --------------------------------------------------------------

    #[Test]
    public function setDataAndGetDataRoundTrip(): void
    {
        $this->base->setData('myKey', 'myValue');

        $this->assertSame('myValue', $this->base->getData('myKey'));
    }

    #[Test]
    public function getDataReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->base->getData('nonexistent'));
    }

    #[Test]
    public function getDataWithNullKeyReturnsEntireArray(): void
    {
        $this->base->setData('key1', 'val1');
        $this->base->setData('key2', 'val2');

        $data = $this->base->getData(null);
        $this->assertIsArray($data);
        $this->assertSame('val1', $data['key1']);
        $this->assertSame('val2', $data['key2']);
    }

    #[Test]
    public function setDataOverwritesPreviousValueForSameKey(): void
    {
        $this->base->setData('key', 'original');
        $this->base->setData('key', 'updated');

        $this->assertSame('updated', $this->base->getData('key'));
    }

    #[Test]
    public function setDataReturnsTrueOnSuccess(): void
    {
        $this->assertTrue($this->base->setData('key', 'value'));
    }

    // --------------------------------------------------------------
    // getDynamicName
    // --------------------------------------------------------------

    #[Test]
    public function getDynamicNameReturnsBaseNameWhenCountIsZero(): void
    {
        $this->base->setName('field');

        $this->assertSame('field', $this->base->getDynamicName(0));
    }

    #[Test]
    public function getDynamicNameAppendsSuffixWhenCountAboveZero(): void
    {
        $this->base->setName('field');

        $this->assertSame('field_3', $this->base->getDynamicName(3));
    }

    // --------------------------------------------------------------
    // Conditions
    // --------------------------------------------------------------

    #[Test]
    public function hasConditionsReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->base->hasConditions());
    }

    #[Test]
    public function getConditionsReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->base->getConditions());
    }

    #[Test]
    public function hasConditionReturnsFalseForNonexistentType(): void
    {
        $this->assertFalse($this->base->hasCondition('visible'));
    }

    #[Test]
    public function getConditionReturnsNullWhenNoConditionExists(): void
    {
        $this->assertNull($this->base->getCondition('required'));
    }

    #[Test]
    public function getShortLabelFallsBackToLabelWhenNoSummaryLabel(): void
    {
        $form = new ValidForm('test');
        $field = $form->addField('name', 'Full Name', ValidForm::VFORM_STRING);

        $this->assertSame('Full Name', $field->getShortLabel());
    }

    #[Test]
    public function getShortLabelReturnsSummaryLabelWhenSet(): void
    {
        $form = new ValidForm('test');
        $field = $form->addField(
            'name',
            'Full Name',
            ValidForm::VFORM_STRING,
            [],
            [],
            ['summaryLabel' => 'Name']
        );

        $this->assertSame('Name', $field->getShortLabel());
    }

    // --------------------------------------------------------------
    // toJS (Base placeholder)
    // --------------------------------------------------------------

    #[Test]
    public function toJsReturnsEmptyStringByDefault(): void
    {
        $this->assertSame('', $this->base->toJS());
    }
}
