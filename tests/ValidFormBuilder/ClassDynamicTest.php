<?php

namespace ValidFormBuilder;

use PHPUnit\Framework\TestCase;

class ClassDynamicTest extends TestCase
{
    /**
     * Test instance of a class extending ClassDynamic
     */
    protected $instance;

    protected function setUp(): void
    {
        $this->instance = new class extends ClassDynamic {
            protected $__testproperty = 'test value';
            protected $__anotherproperty = 42;
        };
    }

    public function testMagicGetterSucceeds(): void
    {
        $this->assertSame('test value', $this->instance->testproperty);
        $this->assertSame(42, $this->instance->anotherproperty);
    }

    public function testMagicGetterThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $nonExistentProperty = $this->instance->nonexistentproperty;
    }

    public function testMagicSetterSucceeds(): void
    {
        $this->instance->testproperty = 'new value';
        $this->assertSame('new value', $this->instance->testproperty);
        
        $this->instance->anotherproperty = 100;
        $this->assertSame(100, $this->instance->anotherproperty);
    }

    public function testMagicSetterThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->instance->nonexistentproperty = 'some value';
    }

    public function testMagicCallerGetMethod(): void
    {
        $this->assertSame('test value', $this->instance->getTestproperty());
        $this->assertSame(42, $this->instance->getAnotherproperty());
    }

    public function testMagicCallerSetMethod(): void
    {
        $this->instance->setTestproperty('updated value');
        $this->assertSame('updated value', $this->instance->testproperty);
        
        $this->instance->setAnotherproperty(200);
        $this->assertSame(200, $this->instance->anotherproperty);
    }

    public function testMagicCallerThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->instance->unknownMethod();
    }
}