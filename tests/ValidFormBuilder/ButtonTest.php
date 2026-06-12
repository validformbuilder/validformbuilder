<?php

namespace ValidFormBuilder\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValidFormBuilder\Button;

class ButtonTest extends TestCase
{
    use HtmlAssertionsTrait;

    protected Button $button;

    protected function setUp(): void
    {
        // Create a default button instance to use in tests
        $this->button = new Button("Test Button");
    }

    #[Test]
    public function getLabel(): void
    {
        $this->assertSame("Test Button", $this->button->getLabel());
    }

    #[Test]
    public function defaultTypeIsSubmit(): void
    {
        $this->assertSame("submit", $this->button->getType());
    }

    #[Test]
    public function customTypeIsReflectedInInputAttribute(): void
    {
        $customButton = new Button("Test Button", ["type" => "button"]);
        $this->assertSame("button", $customButton->getType());

        $xpath = $this->parseHtml($customButton->toHtml());
        // `//input` — Button::toHtml() renders exactly one <input> element; grab it directly.
        $input = $xpath->query('//input')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('button', $input->getAttribute('type'));
    }

    #[Test]
    public function setId(): void
    {
        $customId = "custom-id";
        $this->button->setId($customId);
        $this->assertSame($customId, $this->button->getId());
    }

    #[Test]
    public function defaultConstructedButtonRendersAsSubmitInputWithButtonClass(): void
    {
        // Make sure it has a default generated ID
        $this->assertNotEmpty($this->button->getId());

        $xpath = $this->parseHtml($this->button->toHtml());
        // `//input` — the single <input> element produced by Button::toHtml().
        $input = $xpath->query('//input')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('submit', $input->getAttribute('type'));
        $this->assertSame('Test Button', $input->getAttribute('value'));

        // Class attribute is a space-separated list; tokenise before asserting membership
        // so e.g. `vf__button-disabled` can't accidentally satisfy a `vf__button` check.
        $classTokens = preg_split('/\s+/', (string) $input->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
        $this->assertContains('vf__button', $classTokens);
    }

    #[Test]
    public function getId(): void
    {
        $this->assertNotEmpty($this->button->getId());
    }

    #[Test]
    public function setLabel(): void
    {
        $newLabel = "New Label";
        $this->button->setLabel($newLabel);
        $this->assertSame($newLabel, $this->button->getLabel());
    }

    #[Test]
    public function setType(): void
    {
        $this->button->setType("button");
        $this->assertSame("button", $this->button->getType());
    }

    #[Test]
    public function isValid(): void
    {
        $this->assertTrue($this->button->isValid());
    }

    #[Test]
    public function isDynamic(): void
    {
        $this->assertFalse($this->button->isDynamic());
    }

    #[Test]
    public function hasFields(): void
    {
        $this->assertFalse($this->button->hasFields());
    }

    #[Test]
    public function disabledButtonRendersDisabledAttribute(): void
    {
        // NOTE: Button::toHtml() checks $this->__disabled, but no class in the
        // hierarchy declares a $__disabled property. Because ClassDynamic has no
        // __isset(), `empty($this->__disabled)` is always true for a plain Button
        // and setDisabled() throws BadMethodCallException — the disabled branch
        // is unreachable through the public API. A subclass declaring the
        // property is the only way to exercise (and use) it.
        // The explicit fieldid matters: without it, the generated id embeds the
        // anonymous class name (which contains a NUL byte and a file path), and
        // libxml on some platforms refuses to parse the malformed attribute.
        $button = new class ("Disabled Button", ["fieldid" => "disabled-button"]) extends Button {
            protected $__disabled = true;
        };

        $xpath = $this->parseHtml($button->toHtml());
        // `//input` — the single <input> element; it must carry disabled="disabled".
        $input = $xpath->query('//input')->item(0);

        $this->assertNotNull($input);
        $this->assertSame('disabled', $input->getAttribute('disabled'));
    }

    #[Test]
    public function toHtmlRendersSingleInputWithTypeAndValue(): void
    {
        $customButton = new Button("My Button", ["type" => "button"]);

        $xpath = $this->parseHtml($customButton->toHtml());
        // `//input` — every <input> element in the fragment. A button must render as
        // exactly one <input>, so the length check doubles as a "nothing extra leaked".
        $inputs = $xpath->query('//input');

        $this->assertSame(1, $inputs->length);
        $this->assertSame('button', $inputs->item(0)->getAttribute('type'));
        $this->assertSame('My Button', $inputs->item(0)->getAttribute('value'));
    }
}
