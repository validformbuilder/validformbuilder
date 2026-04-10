<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\ClassDynamic;

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

    #[Test]
    public function magicGetterSucceeds(): void
    {
        $this->assertSame('test value', $this->instance->testproperty);
        $this->assertSame(42, $this->instance->anotherproperty);
    }

    #[Test]
    public function magicGetterThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $nonExistentProperty = $this->instance->nonexistentproperty;
    }

    #[Test]
    public function magicSetterSucceeds(): void
    {
        $this->instance->testproperty = 'new value';
        $this->assertSame('new value', $this->instance->testproperty);
        
        $this->instance->anotherproperty = 100;
        $this->assertSame(100, $this->instance->anotherproperty);
    }

    #[Test]
    public function magicSetterThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->instance->nonexistentproperty = 'some value';
    }

    #[Test]
    public function magicCallerGetMethod(): void
    {
        $this->assertSame('test value', $this->instance->getTestproperty());
        $this->assertSame(42, $this->instance->getAnotherproperty());
    }

    #[Test]
    public function magicCallerSetMethod(): void
    {
        $this->instance->setTestproperty('updated value');
        $this->assertSame('updated value', $this->instance->testproperty);
        
        $this->instance->setAnotherproperty(200);
        $this->assertSame(200, $this->instance->anotherproperty);
    }

    #[Test]
    public function magicCallerThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->instance->unknownMethod();
    }
}