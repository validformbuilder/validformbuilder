<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Base;
use ValidFormBuilder\Comparison;
use ValidFormBuilder\Condition;
use ValidFormBuilder\Element;
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

    protected function tearDown(): void
    {
        // Clean up any request variables we might have set
        if (isset($_REQUEST['base-trigger'])) {
            unset($_REQUEST['base-trigger']);
        }
    }

    /**
     * Build a form with a trigger field and a subject field where the subject
     * has a condition of type $property which is met as soon as the trigger
     * field is not empty.
     */
    private function buildConditionSubject(string $property, bool $value, ?string $triggerValue = null): Element
    {
        $form = new ValidForm('base-condition-form');
        $trigger = $form->addField('base-trigger', 'Trigger', ValidForm::VFORM_STRING);
        $subject = $form->addField('base-subject', 'Subject', ValidForm::VFORM_STRING);

        $subject->addCondition($property, $value, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_NOT_EMPTY)
        ]);

        if (!is_null($triggerValue)) {
            $_REQUEST['base-trigger'] = $triggerValue;
        }

        return $subject;
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
    public function setConditionsOverwritesExistingConditions(): void
    {
        // setConditions() resolves through ClassDynamic::__call and overwrites
        // the full $__conditions array, unlike addCondition() which appends.
        // Pins the current magic behavior until #157 deprecates/removes it.
        $subject = $this->buildConditionSubject('visible', true);
        $this->assertCount(1, $subject->getConditions());

        $condition = $subject->getConditions()[0];

        $subject->setConditions([]);
        $this->assertSame([], $subject->getConditions());

        $subject->setConditions([$condition]);
        $this->assertSame([$condition], $subject->getConditions());
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

    // --------------------------------------------------------------
    // addCondition
    // --------------------------------------------------------------

    #[Test]
    public function addConditionAddsNewConditionToElement(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->assertTrue($subject->hasCondition('visible'));
        $this->assertFalse($subject->hasCondition('enabled'));
        $this->assertTrue($subject->hasConditions());
        $this->assertCount(1, $subject->getConditions());
        $this->assertInstanceOf(Condition::class, $subject->getCondition('visible'));
    }

    #[Test]
    public function addConditionAcceptsComparisonAsArray(): void
    {
        $form = new ValidForm('base-condition-form');
        $trigger = $form->addField('base-trigger', 'Trigger', ValidForm::VFORM_STRING);
        $subject = $form->addField('base-subject', 'Subject', ValidForm::VFORM_STRING);

        $subject->addCondition('visible', true, [
            [$trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes']
        ]);

        $condition = $subject->getCondition('visible');
        $this->assertInstanceOf(Condition::class, $condition);
        $this->assertCount(1, $condition->getComparisons());
    }

    #[Test]
    public function addConditionReusesExistingConditionOfSameType(): void
    {
        $form = new ValidForm('base-condition-form');
        $trigger = $form->addField('base-trigger', 'Trigger', ValidForm::VFORM_STRING);
        $subject = $form->addField('base-subject', 'Subject', ValidForm::VFORM_STRING);

        $subject->addCondition('visible', true, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_NOT_EMPTY)
        ]);
        $subject->addCondition('visible', true, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'yes')
        ]);

        // The second call re-uses the existing Condition object and only adds
        // the new comparison to it. Note: the same condition instance is
        // pushed onto the internal conditions array again.
        $conditions = $subject->getConditions();
        $this->assertCount(2, $conditions);
        $this->assertSame($conditions[0], $conditions[1]);
        $this->assertCount(2, $conditions[0]->getComparisons());
    }

    #[Test]
    public function addConditionThrowsOnInvalidComparisonObject(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or no comparison(s) supplied.');

        $subject->addCondition('enabled', true, [new \stdClass()]);
    }

    #[Test]
    public function addConditionThrowsOnEmptyComparisonsArray(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or no comparison(s) supplied.');

        $subject->addCondition('enabled', true, []);
    }

    // --------------------------------------------------------------
    // getCondition / getMetCondition / getConditionRecursive
    // --------------------------------------------------------------

    #[Test]
    public function getConditionReturnsMatchingCondition(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $condition = $subject->getCondition('visible');

        $this->assertInstanceOf(Condition::class, $condition);
        $this->assertSame('visible', $condition->getProperty());
    }

    #[Test]
    public function getConditionFallsBackToParentCondition(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->base->setParent($subject);

        $this->assertSame($subject->getCondition('visible'), $this->base->getCondition('visible'));
    }

    #[Test]
    public function getMetConditionReturnsConditionWhenMet(): void
    {
        $subject = $this->buildConditionSubject('visible', true, 'not empty');

        $condition = $subject->getMetCondition('visible');

        $this->assertInstanceOf(Condition::class, $condition);
        $this->assertSame($subject->getCondition('visible'), $condition);
    }

    #[Test]
    public function getMetConditionReturnsNullWhenConditionNotMet(): void
    {
        // The trigger field is left empty, so the condition is not met.
        $subject = $this->buildConditionSubject('visible', true);

        $this->assertNull($subject->getMetCondition('visible'));
    }

    #[Test]
    public function getMetConditionFallsBackToParentCondition(): void
    {
        $subject = $this->buildConditionSubject('visible', true, 'not empty');

        $this->base->setParent($subject);

        $this->assertSame($subject->getCondition('visible'), $this->base->getMetCondition('visible'));
    }

    #[Test]
    public function getConditionRecursiveReturnsOwnCondition(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->assertSame($subject->getCondition('visible'), $subject->getConditionRecursive('visible'));
    }

    // --------------------------------------------------------------
    // getDynamicButtonMeta
    // --------------------------------------------------------------

    #[Test]
    public function getDynamicButtonMetaReturnsEmptyStringWithoutVisibleCondition(): void
    {
        $this->assertSame('', $this->base->getDynamicButtonMeta());
    }

    #[Test]
    public function getDynamicButtonMetaShowsButtonWhenMetConditionValueIsTrue(): void
    {
        $subject = $this->buildConditionSubject('visible', true, 'not empty');

        $this->assertSame('', $subject->getDynamicButtonMeta());
    }

    #[Test]
    public function getDynamicButtonMetaHidesButtonWhenMetConditionValueIsFalse(): void
    {
        $subject = $this->buildConditionSubject('visible', false, 'not empty');

        $this->assertSame(' style="display:none;"', $subject->getDynamicButtonMeta());
    }

    #[Test]
    public function getDynamicButtonMetaHidesButtonWhenUnmetConditionValueIsTrue(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $this->assertSame(' style="display:none;"', $subject->getDynamicButtonMeta());
    }

    #[Test]
    public function getDynamicButtonMetaShowsButtonWhenUnmetConditionValueIsFalse(): void
    {
        $subject = $this->buildConditionSubject('visible', false);

        $this->assertSame('', $subject->getDynamicButtonMeta());
    }

    // --------------------------------------------------------------
    // setConditionalMeta
    // --------------------------------------------------------------

    #[Test]
    public function setConditionalMetaSetsDisplayBlockWhenMetVisibleConditionIsTrue(): void
    {
        $subject = $this->buildConditionSubject('visible', true, 'not empty');

        $subject->setConditionalMeta();

        $this->assertSame('display: block;', $subject->getMeta('style'));
    }

    #[Test]
    public function setConditionalMetaSetsDisplayNoneWhenMetVisibleConditionIsFalse(): void
    {
        $subject = $this->buildConditionSubject('visible', false, 'not empty');

        $subject->setConditionalMeta();

        $this->assertSame('display: none;', $subject->getMeta('style'));
    }

    #[Test]
    public function setConditionalMetaSetsDisplayNoneWhenUnmetVisibleConditionIsTrue(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $subject->setConditionalMeta();

        $this->assertSame('display: none;', $subject->getMeta('style'));
    }

    #[Test]
    public function setConditionalMetaSetsDisplayBlockWhenUnmetVisibleConditionIsFalse(): void
    {
        $subject = $this->buildConditionSubject('visible', false);

        $subject->setConditionalMeta();

        $this->assertSame('display: block;', $subject->getMeta('style'));
    }

    #[Test]
    public function setConditionalMetaEnablesFieldWhenMetEnabledConditionIsTrue(): void
    {
        $subject = $this->buildConditionSubject('enabled', true, 'not empty');
        $subject->setFieldMeta('disabled', 'disabled', true);

        $subject->setConditionalMeta();

        // The 'disabled' attribute is removed again.
        $this->assertSame('', $subject->getFieldMeta('disabled'));
    }

    #[Test]
    public function setConditionalMetaDisablesFieldWhenMetEnabledConditionIsFalse(): void
    {
        $subject = $this->buildConditionSubject('enabled', false, 'not empty');

        $subject->setConditionalMeta();

        $this->assertSame('disabled', $subject->getFieldMeta('disabled'));
    }

    #[Test]
    public function setConditionalMetaDisablesFieldWhenUnmetEnabledConditionIsTrue(): void
    {
        $subject = $this->buildConditionSubject('enabled', true);

        $subject->setConditionalMeta();

        $this->assertSame('disabled', $subject->getFieldMeta('disabled'));
    }

    #[Test]
    public function setConditionalMetaEnablesFieldWhenUnmetEnabledConditionIsFalse(): void
    {
        $subject = $this->buildConditionSubject('enabled', false);
        $subject->setFieldMeta('disabled', 'disabled', true);

        $subject->setConditionalMeta();

        $this->assertSame('', $subject->getFieldMeta('disabled'));
    }

    // --------------------------------------------------------------
    // conditionsToJs / matchWithToJs
    // --------------------------------------------------------------

    #[Test]
    public function conditionsToJsEmitsAddConditionCall(): void
    {
        $subject = $this->buildConditionSubject('visible', true);

        $js = $subject->toJS();

        $this->assertStringContainsString('objForm.addCondition(', $js);
        $this->assertStringContainsString('"property":"visible"', $js);
    }

    #[Test]
    public function conditionsToJsEncodesComparisonValuesAsJson(): void
    {
        // SECURITY: condition data is serialized with json_encode(), so
        // comparison values cannot break out of the generated javascript.
        $form = new ValidForm('base-condition-form');
        $trigger = $form->addField('base-trigger', 'Trigger', ValidForm::VFORM_STRING);
        $subject = $form->addField('base-subject', 'Subject', ValidForm::VFORM_STRING);

        $subject->addCondition('visible', true, [
            new Comparison($trigger, ValidForm::VFORM_COMPARISON_EQUAL, 'a"b</script>')
        ]);

        $js = $subject->toJS();

        $this->assertStringContainsString('a\"b<\/script>', $js);
        $this->assertStringNotContainsString('a"b</script>', $js);
    }

    #[Test]
    public function matchWithToJsEmitsMatchfieldsCall(): void
    {
        $form = new ValidForm('base-match-form');
        $password = $form->addField('base-password', 'Password', ValidForm::VFORM_PASSWORD);
        $confirm = $form->addField(
            'base-password-confirm',
            'Confirm password',
            ValidForm::VFORM_PASSWORD,
            ['matchWith' => $password]
        );

        $js = $confirm->toJS();

        $this->assertStringContainsString('objForm.matchfields(', $js);
        $this->assertStringContainsString("'base-password'", $js);
    }

    // --------------------------------------------------------------
    // getCountersRecursive (via Area::getDynamicCount)
    // --------------------------------------------------------------

    #[Test]
    public function getDynamicCountCollectsDynamicCountersRecursively(): void
    {
        $form = new ValidForm('base-counters-form');
        $area = $form->addArea('Dynamic area', false, 'base-area', false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another'
        ]);

        // Adding a field to a dynamic area also adds a hidden dynamic counter.
        $area->addField('base-area-field', 'Area field', ValidForm::VFORM_STRING);

        // A multifield inside the area forces getCountersRecursive() to recurse.
        $multiField = $area->addMultiField('Multi');
        $multiField->addField('base-multi-field', ValidForm::VFORM_STRING);

        // No submission, so the counter values default to zero.
        $this->assertSame(0, $area->getDynamicCount());
    }

    // --------------------------------------------------------------
    // getRemoveLabelHtml / dynamic (remove) label meta strings
    // --------------------------------------------------------------

    #[Test]
    public function getRemoveLabelHtmlRendersAnchorWithDynamicRemoveLabelMeta(): void
    {
        $this->base->setDynamicRemoveLabelMeta('class', 'vf__removeLabel');

        $ref = new \ReflectionMethod($this->base, 'getRemoveLabelHtml');
        $ref->setAccessible(true);

        $this->assertSame(
            "<a class=\"vf__removeLabel\" href='#'>Remove me</a>",
            $ref->invoke($this->base, 'Remove me')
        );
    }

    #[Test]
    public function getRemoveLabelHtmlFallsBackToDynamicRemoveLabelProperty(): void
    {
        $ref = new \ReflectionMethod($this->base, 'getRemoveLabelHtml');
        $ref->setAccessible(true);

        // No label argument and no __dynamicRemoveLabel set; renders an empty anchor.
        $this->assertSame("<a href='#'></a>", $ref->invoke($this->base));
    }

    #[Test]
    public function dynamicLabelMetaIsRenderedOnDynamicAnchor(): void
    {
        $form = new ValidForm('base-dynamic-form');
        $field = $form->addField('base-dynamic-field', 'Dynamic field', ValidForm::VFORM_STRING, [], [], [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
            'dynamicLabelClass' => 'add-button'
        ]);

        $html = $field->toHtml();

        $this->assertStringContainsString('class="add-button"', $html);
        $this->assertStringContainsString('Add another', $html);
    }

    #[Test]
    public function labelMetaIsRenderedOnLabelElement(): void
    {
        $form = new ValidForm('base-label-form');
        $field = $form->addField('base-labeled-field', 'Labeled field', ValidForm::VFORM_STRING, [], [], [
            'labelClass' => 'fancy-label'
        ]);

        $html = $field->toHtml();

        $this->assertStringContainsString('class="fancy-label"', $html);
    }

    // --------------------------------------------------------------
    // __initializeMeta / __setMeta internals
    // --------------------------------------------------------------

    #[Test]
    public function initializeMetaPrefixesReservedLabelMetaKeys(): void
    {
        // Base itself defines no reserved label meta keys; subclasses can.
        $base = new class extends Base {
            protected $__reservedlabelmeta = ['range'];
        };

        $base->setMeta('range', '1-10');

        $ref = new \ReflectionMethod($base, '__initializeMeta');
        $ref->setAccessible(true);
        $ref->invoke($base);

        $this->assertSame('1-10', $base->getLabelMeta('range'));
    }

    #[Test]
    public function setMetaKeepsExistingIdWhenNotOverwriting(): void
    {
        $this->base->setMeta('id', 'original');

        $this->assertSame('original', $this->base->setMeta('id', 'ignored'));
        $this->assertSame('original', $this->base->getMeta('id'));
    }

    #[Test]
    public function setMetaAppendsStyleWithSemicolonDelimiter(): void
    {
        $this->base->setMeta('style', 'color: red;');
        $this->base->setMeta('style', 'width: 10px;');

        $this->assertSame('color: red;width: 10px;', $this->base->getMeta('style'));
    }
}
