<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Element;
use ValidFormBuilder\Hidden;
use ValidFormBuilder\MultiField;
use ValidFormBuilder\Paragraph;
use ValidFormBuilder\ValidForm;

class AreaTest extends TestCase
{
    use HtmlAssertionsTrait;

    private Area $area;

    protected function setUp(): void
    {
        $this->area = new Area("Test Area", true, "test-area", false, []);
    }

    #[Test]
    public function getLabel(): void
    {
        $this->assertSame("Test Area", $this->area->getLabel());
    }

    #[Test]
    public function setLabel(): void
    {
        $this->area->setLabel("New Label");
        $this->assertSame("New Label", $this->area->getLabel());
    }

    #[Test]
    public function getRequiredStyle(): void
    {
        // TODO: Make $__requiredstyle a string type and set the default value to "".
        //   Now it defaults to null which could be unexpected, since the type is softly defined as non-nullable string.
        $this->assertNull($this->area->getRequiredStyle());
    }

    #[Test]
    public function setRequiredStyle(): void
    {
        $this->area->setRequiredStyle("%s *");
        $this->assertSame("%s *", $this->area->getRequiredStyle());
    }

    #[Test]
    public function addField(): void
    {
        $field = $this->area->addField("test-field", "Test Field", ValidForm::VFORM_STRING);
        $this->assertInstanceOf(Element::class, $field);
    }

    #[Test]
    public function addParagraph(): void
    {
        $paragraph = $this->area->addParagraph("This is a test paragraph.", "Test Header");
        $this->assertInstanceOf(Paragraph::class, $paragraph);
    }

    #[Test]
    public function addMultiField(): void
    {
        $multiField = $this->area->addMultiField("MultiField Test");
        $this->assertInstanceOf(MultiField::class, $multiField);
    }

    #[Test]
    public function activeAreaRendersFieldsetWithCheckboxInLegend(): void
    {
        // The default $this->area is an active area (second ctor arg = true) with
        // a default checked value of false. Expected structure:
        //
        //   <fieldset class="vf__area vf__disabled" id="test-area_wrapper">
        //     <legend>
        //       <label for="test-area">
        //         <input type="checkbox" name="test-area" id="test-area" />
        //         Test Area
        //       </label>
        //     </legend>
        //   </fieldset>
        $xpath = $this->parseHtml($this->area->toHtml());

        // `//fieldset` — the single <fieldset> element rendered by Area::toHtml().
        $fieldset = $xpath->query('//fieldset')->item(0);
        $this->assertNotNull($fieldset);
        $this->assertSame('test-area_wrapper', $fieldset->getAttribute('id'));

        $classTokens = preg_split('/\s+/', (string) $fieldset->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__area', $classTokens);
        $this->assertContains('vf__disabled', $classTokens);

        // `//fieldset/legend` — <legend> as a direct child of <fieldset>; expect exactly one.
        $this->assertSame(1, $xpath->query('//fieldset/legend')->length);

        // `//fieldset/legend/label/input[@type="checkbox"]` — walk the direct-child chain
        // fieldset → legend → label → input, and match only inputs whose type attribute is
        // "checkbox". This is the active-area toggle that Area renders inside its legend.
        $checkbox = $xpath->query('//fieldset/legend/label/input[@type="checkbox"]')->item(0);
        $this->assertNotNull($checkbox);
        $this->assertSame('test-area', $checkbox->getAttribute('name'));
        $this->assertSame('test-area', $checkbox->getAttribute('id'));
    }

    #[Test]
    public function inactiveAreaRendersLegendWithPlainHeader(): void
    {
        // Default-constructed Area is inactive, so the legend contains plain text,
        // not a wrapping checkbox.
        $area = new Area("Test Area");
        $xpath = $this->parseHtml($area->toHtml());

        // `//fieldset/legend` — <legend> as a direct child of <fieldset>.
        $legend = $xpath->query('//fieldset/legend')->item(0);
        $this->assertNotNull($legend);
        $this->assertSame('Test Area', trim($legend->textContent));

        // `//fieldset/legend//input[@type="checkbox"]` — any <input type="checkbox"> *anywhere*
        // inside <legend> (double slash = descendant-or-self, any depth). Expect zero matches
        // for an inactive area.
        $this->assertSame(0, $xpath->query('//fieldset/legend//input[@type="checkbox"]')->length);
    }

    #[Test]
    public function hasContent(): void
    {
        $this->assertFalse($this->area->hasContent());
    }

    #[Test]
    public function isActive(): void
    {
        $area = new Area("Test Area", true, "test-area", false, []);
        $this->assertTrue($area->isActive());

        $area = new Area("Test Area", false, "test-area", false, []);
        $this->assertFalse($area->isActive());

        $area = new Area("Test Area with defaults");
        $this->assertFalse($area->isActive());
    }

    #[Test]
    public function isValid(): void
    {
        $this->assertTrue($this->area->isValid());
    }

    #[Test]
    public function getDynamicCount(): void
    {
        $this->assertSame(0, $this->area->getDynamicCount());
    }

    #[Test]
    public function getFields(): void
    {
        $fields = $this->area->getFields();
        $this->assertInstanceOf(Collection::class, $fields);
    }

    /**
     * If this is an active Area, getValue returns the value of the Checkbox.
     * @return void
     */
    #[Test]
    public function getValue(): void
    {
        $area = new Area("Test Area", true, "test-area_wrapper", false, []);
        $this->assertFalse($area->getValue());

        // This check is disabled for now, while issue #153 is pending.
        //$area = new Area("Test Area", true, "test-area_wrapper", true, []);
        //$this->assertTrue($area->getValue());

        // It's not an active area, so it's value is always 'true'.
        $area = new Area("Test Area", false, "test-area_wrapper", false, []);
        $this->assertTrue($area->getValue());
    }

    #[Test]
    public function getId(): void
    {
        $this->assertSame("test-area_wrapper", $this->area->getId());
    }

    #[Test]
    public function getType(): void
    {
        $this->assertSame(0, $this->area->getType());
    }

    #[Test]
    public function hasFields(): void
    {
        $this->assertFalse($this->area->hasFields());
    }

    #[Test]
    public function hasFieldsWithFields(): void
    {
        $this->assertFalse($this->area->hasFields());
        $this->area->addField("test-field", "Test Field", ValidForm::VFORM_STRING);
        $this->assertTrue($this->area->hasFields());
    }

    // --------------------------------------------------------------
    // hasContent — with submitted data
    // --------------------------------------------------------------

    #[Test]
    public function hasContentReturnsTrueWhenFieldHasSubmittedValue(): void
    {
        $area = new Area("Content Area", false, "content-area");
        $area->addField("filled", "Filled", ValidForm::VFORM_STRING);

        $_REQUEST['filled'] = 'hello';

        $this->assertTrue($area->hasContent());

        unset($_REQUEST['filled']);
    }

    // --------------------------------------------------------------
    // toJS
    // --------------------------------------------------------------

    #[Test]
    public function toJsReturnsEmptyStringForEmptyArea(): void
    {
        $area = new Area("Empty Area");

        $this->assertSame('', $area->toJS());
    }

    #[Test]
    public function toJsRendersChildFieldJavascript(): void
    {
        $area = new Area("JS Area", false, "js-area");
        $area->addField("js-field", "JS Field", ValidForm::VFORM_STRING);

        $js = $area->toJS();

        // The child Text field generates an addElement call.
        $this->assertStringContainsString('objForm.addElement', $js);
    }

    // --------------------------------------------------------------
    // addField / addMultiField — dynamic area behaviour
    // --------------------------------------------------------------

    #[Test]
    public function addFieldOnDynamicAreaInjectsHiddenDynamicCounter(): void
    {
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $area->addField("phone", "Phone", ValidForm::VFORM_STRING);

        // Two children: the Text field + the hidden dynamic counter.
        $this->assertSame(2, $area->getFields()->count());

        $counter = $area->getFields()->getLast();
        $this->assertInstanceOf(Hidden::class, $counter);
        $this->assertTrue($counter->isDynamicCounter());
        $this->assertSame('phone_dynamic', $counter->getName());
    }

    #[Test]
    public function addFieldHonoursDynamicCountMetaAsCounterDefault(): void
    {
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $area->addField("phone", "Phone", ValidForm::VFORM_STRING, [], [], [
            'dynamicCount' => 2,
        ]);

        // The 'dynamicCount' meta seeds the hidden counter's default value,
        // which getDynamicCount() picks up without any submitted data.
        $this->assertSame(2, $area->getDynamicCount());
    }

    #[Test]
    public function addMultiFieldInsideDynamicAreaForcesDynamicChild(): void
    {
        // A dynamic Area overwrites the child's dynamic settings: a multifield
        // inside a dynamic area is always dynamic itself (with an empty
        // dynamicLabel, since duplication is controlled by the area).
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $multiField = $area->addMultiField("MultiField Test");

        $this->assertTrue($multiField->isDynamic());
    }

    // --------------------------------------------------------------
    // hasContent — multifield children
    // --------------------------------------------------------------

    #[Test]
    public function hasContentChecksMultiFieldChildren(): void
    {
        $area = new Area("Content Area", false, "content-area");
        $multiField = $area->addMultiField("Full name");
        $multiField->addField("mf-first-name", ValidForm::VFORM_STRING);

        $_REQUEST['mf-first-name'] = 'Robin';

        // The MultiField branch of hasContent() delegates to MultiField::hasContent().
        $this->assertTrue($area->hasContent());

        unset($_REQUEST['mf-first-name']);
    }

    // --------------------------------------------------------------
    // toHtml — active area checked states
    // --------------------------------------------------------------

    #[Test]
    public function activeCheckedAreaRendersCheckedCheckboxBeforeSubmission(): void
    {
        // Active + checked-by-default and not yet submitted: the legend
        // checkbox carries the checked attribute and the fieldset is enabled.
        $area = new Area("Checked Area", true, "checked-area", true);

        $xpath = $this->parseHtml($area->toHtml());

        // `//fieldset/legend/label/input[@type="checkbox"]` — the active-area toggle.
        $checkbox = $xpath->query('//fieldset/legend/label/input[@type="checkbox"]')->item(0);
        $this->assertNotNull($checkbox);
        $this->assertSame('checked', $checkbox->getAttribute('checked'));

        // Because the checkbox is checked, the area must not get vf__disabled.
        $fieldset = $xpath->query('//fieldset')->item(0);
        $classTokens = preg_split('/\s+/', (string) $fieldset->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotContains('vf__disabled', $classTokens);
    }

    #[Test]
    public function activeAreaStaysCheckedAfterSubmissionWithContent(): void
    {
        // Active area submitted with child content: the checkbox re-renders checked.
        $area = new Area("Active Area", true, "active-area");
        $area->addField("act-field", "Field", ValidForm::VFORM_STRING);

        $_REQUEST['act-field'] = 'some value';

        $xpath = $this->parseHtml($area->toHtml(true));

        $checkbox = $xpath->query('//fieldset/legend/label/input[@type="checkbox"]')->item(0);
        $this->assertNotNull($checkbox);
        $this->assertSame('checked', $checkbox->getAttribute('checked'));

        // The child field renders inside the fieldset with the submitted value.
        $input = $xpath->query('//fieldset//input[@name="act-field"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('some value', $input->getAttribute('value'));

        unset($_REQUEST['act-field']);
    }

    // --------------------------------------------------------------
    // toHtml — dynamic area rendering (original + clones)
    // --------------------------------------------------------------

    #[Test]
    public function dynamicAreaRendersOriginalAndCloneFieldsets(): void
    {
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $area->addField("phone", "Phone", ValidForm::VFORM_STRING);

        // Simulate a submission where the user duplicated the area once.
        $_REQUEST['phone_dynamic'] = '1';

        $xpath = $this->parseHtml($area->toHtml());

        // `//fieldset` — one original + one clone.
        $fieldsets = $xpath->query('//fieldset');
        $this->assertSame(2, $fieldsets->length);

        // The original carries the wrapper id and data-dynamic="original".
        $this->assertSame('dyn-area_wrapper', $fieldsets->item(0)->getAttribute('id'));
        $this->assertSame('original', $fieldsets->item(0)->getAttribute('data-dynamic'));

        // The clone has no id, data-dynamic="clone" and the vf__clone class.
        $this->assertSame('', $fieldsets->item(1)->getAttribute('id'));
        $this->assertSame('clone', $fieldsets->item(1)->getAttribute('data-dynamic'));
        $cloneClassTokens = preg_split('/\s+/', (string) $fieldsets->item(1)->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__clone', $cloneClassTokens);

        // The clone renders the child field with the _1 suffix.
        $this->assertSame(1, $xpath->query('//input[@name="phone_1"]')->length);

        // The hidden dynamic counter renders exactly once (skipped in clones).
        $this->assertSame(1, $xpath->query('//input[@name="phone_dynamic"]')->length);

        // The duplication trigger renders once, after the last fieldset.
        // `//div[@class="vf__dynamic"]/a` — the 'add another' anchor with its field targets.
        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('phone', $anchor->getAttribute('data-target-id'));
        $this->assertSame('phone', $anchor->getAttribute('data-target-name'));
        $this->assertSame('Add another', trim($anchor->textContent));

        unset($_REQUEST['phone_dynamic']);
    }

    #[Test]
    public function dynamicActiveAreaIncludesCheckboxInDynamicTargets(): void
    {
        // For an active dynamic area, the area's own checkbox name must be part
        // of the duplication targets so the client can clone it too.
        $area = new Area("Active Dynamic", true, "act-dyn", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $area->addField("contact", "Contact", ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($area->toHtml());

        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);
        $this->assertSame('contact|act-dyn', $anchor->getAttribute('data-target-id'));
        $this->assertSame('contact|act-dyn', $anchor->getAttribute('data-target-name'));

        // The active area's legend also embeds the hidden counter input.
        $this->assertSame(1, $xpath->query('//legend/label/input[@name="act-dyn_dynamic"]')->length);
    }

    #[Test]
    public function dynamicAreaIncludesMultiFieldChildrenInDynamicTargets(): void
    {
        // MultiField children contribute their *subfields* to the duplication
        // targets, while hidden dynamic counter subfields are skipped.
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $multiField = $area->addMultiField("Full name");
        $multiField->addField("mf-first", ValidForm::VFORM_STRING);
        $multiField->addField("mf-last", ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($area->toHtml());

        $anchor = $xpath->query('//div[@class="vf__dynamic"]/a')->item(0);
        $this->assertNotNull($anchor);

        // Both subfields are listed; their hidden counters (mf-first_dynamic,
        // mf-last_dynamic) are not.
        $this->assertSame('mf-first|mf-last', $anchor->getAttribute('data-target-id'));
        $this->assertSame('mf-first|mf-last', $anchor->getAttribute('data-target-name'));
    }

    #[Test]
    public function removableAreaRendersRemoveLabelAndClass(): void
    {
        $area = new Area("Removable Area", false, "removable-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
            'dynamicRemoveLabel' => 'Remove this area',
        ]);
        $area->addField("rem-field", "Field", ValidForm::VFORM_STRING);

        $xpath = $this->parseHtml($area->toHtml());

        // `//fieldset` — the wrapper carries the vf__removable class.
        $fieldset = $xpath->query('//fieldset')->item(0);
        $classTokens = preg_split('/\s+/', (string) $fieldset->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__removable', $classTokens);

        // `//fieldset/a[@class="vf__removeLabel"]` — the remove anchor inside the fieldset.
        $removeAnchor = $xpath->query('//fieldset/a[@class="vf__removeLabel"]')->item(0);
        $this->assertNotNull($removeAnchor);
        $this->assertSame('Remove this area', trim($removeAnchor->textContent));
    }

    // --------------------------------------------------------------
    // isValid / getDynamicCount — submitted data
    // --------------------------------------------------------------

    #[Test]
    public function isValidReturnsFalseWhenChildFieldIsInvalid(): void
    {
        // A non-active area propagates child validation failures. (Active areas
        // without content are exempt — an unchecked active area is always valid.)
        $area = new Area("Validation Area", false, "validation-area");
        $area->addField(
            "required-area-field",
            "Required",
            ValidForm::VFORM_STRING,
            ['required' => true],
            ['required' => 'This field is required']
        );

        $this->assertFalse($area->isValid());
    }

    #[Test]
    public function getDynamicCountReadsSubmittedCounterValue(): void
    {
        $area = new Area("Dynamic Area", false, "dyn-area", false, [
            'dynamic' => true,
            'dynamicLabel' => 'Add another',
        ]);
        $area->addField("phone", "Phone", ValidForm::VFORM_STRING);

        $_REQUEST['phone_dynamic'] = '3';

        // The submitted counter value drives the dynamic count. NOTE: unlike
        // Element::getDynamicCount(), Area::getDynamicCount() does not cast to
        // int — the raw request string leaks through (docblock promises integer).
        $this->assertSame('3', $area->getDynamicCount());

        unset($_REQUEST['phone_dynamic']);
    }
}
