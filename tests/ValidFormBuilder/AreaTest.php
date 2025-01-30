<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Area;

class AreaTest extends TestCase
{
    protected $area;

    protected function setUp(): void
    {
        $this->area = new Area('Test Area', true, 'test_area', false, ['class' => 'custom-class']);
    }

    public function testAreaInitialization(): void
    {
        $this->assertEquals('Test Area', $this->area->getLabel());
        $this->assertEquals('test_area', $this->area->getName());
        $this->assertTrue($this->area->isActive());
        $this->assertEquals(['class' => 'custom-class'], $this->area->getMeta());
    }

    public function testSetAndGetLabel(): void
    {
        $this->area->setLabel('New Label');
        $this->assertEquals('New Label', $this->area->getLabel());
    }

    public function testSetAndGetName(): void
    {
        $this->area->setName('new_name');
        $this->assertEquals('new_name', $this->area->getName());
    }

    public function testSetAndGetActive(): void
    {
        $this->area->setActive(false);
        $this->assertFalse($this->area->isActive());
    }

    public function testSetAndGetMeta(): void
    {
        $newMeta = ['id' => 'area1', 'class' => 'new-class'];
        $this->area->setMeta($newMeta);
        $this->assertEquals($newMeta, $this->area->getMeta());
    }

    public function testAddField(): void
    {
        $field = $this->createMock('ValidFormBuilder\Field');
        $this->area->addField($field);
        $this->assertContains($field, $this->area->getFields());
    }

    public function testAddParagraph(): void
    {
        $paragraph = $this->createMock('ValidFormBuilder\Paragraph');
        $this->area->addParagraph($paragraph);
        $this->assertContains($paragraph, $this->area->getElements());
    }

    public function testToJS(): void
    {
        $js = $this->area->toJS();
        $this->assertIsString($js);
        $this->assertStringContainsString('Test Area', $js);
    }
}
