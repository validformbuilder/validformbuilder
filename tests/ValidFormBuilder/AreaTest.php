<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;
use ValidFormBuilder\Collection;
use ValidFormBuilder\Element;
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
}
