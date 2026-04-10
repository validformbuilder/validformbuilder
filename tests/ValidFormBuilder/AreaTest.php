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
    public function toHtml(): void
    {
        /**
         * The default $this->area is an active area with a default checked value of true.
         * Expected HTML output:
         *
         * <fieldset class="vf__area vf__disabled" id="test-area_wrapper">
         * <legend><label for="test-area"><input type="checkbox" name="test-area" id="test-area" /> Test Area</label></legend>
         * </fieldset>
         */
        $htmlOutput = $this->area->toHtml();
        $this->assertIsString($htmlOutput);
        // This is an active area with a default checked value of false, it should render the vf__disabled class
        $this->assertStringContainsString("<fieldset class=\"vf__area vf__disabled\" id=\"test-area_wrapper\">", $htmlOutput);
        $this->assertStringContainsString("</fieldset>", $htmlOutput);
        $this->assertStringContainsString("<legend>", $htmlOutput);
        $this->assertStringContainsString("</legend>", $htmlOutput);

        // It should also render a checkbox to enable this area
        $this->assertStringContainsString("<label for=\"test-area\"><input type=\"checkbox\" name=\"test-area\" id=\"test-area\" />", $htmlOutput);

        /**
         * Now let's test against the default values of the Area class
         * Expected output:
         *
         * <fieldset class="vf__area" id="area_[random number]_wrapper">
         * <legend>Test Area</legend>
         * </fieldset>
         */
        $area = new Area("Test Area");
        $htmlOutput = $area->toHtml();
        $this->assertIsString($htmlOutput);
        $this->assertStringContainsString("<legend>Test Area</legend>", $htmlOutput);
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
}
