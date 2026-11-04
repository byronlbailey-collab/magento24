<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Model\Product\Option\Type;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Model\Product\Option\Type\ColorSwatch;
use Magento\Catalog\Model\Product\Option;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\StringUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ColorSwatchTest extends TestCase
{
    private OptionColors&MockObject $colors;
    private ColorSwatch $type;

    protected function setUp(): void
    {
        $this->colors = $this->createMock(OptionColors::class);
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(fn ($v) => htmlspecialchars((string)$v));
        $escaper->method('escapeHtmlAttr')->willReturnCallback(fn ($v) => htmlspecialchars((string)$v));
        $this->type = new ColorSwatch(
            $this->createMock(Session::class),
            $this->createMock(ScopeConfigInterface::class),
            new StringUtils(),
            $escaper,
            $this->colors,
            [],
            ['color_swatch' => 'color_swatch']
        );
        $value = new class extends \Magento\Framework\DataObject {
            public function getTitle() { return 'Blue <b>'; }
        };
        $option = $this->createPartialMock(Option::class, ['getValueById', 'getId']);
        $option->method('getId')->willReturn(18);
        $option->method('getValueById')->willReturnCallback(fn ($id) => (int)$id === 7 ? $value : null);
        $option->setData(['title' => 'Imprint Color', 'type' => 'color_swatch']);
        $this->type->setOption($option);
    }

    public function testItIsACustomizedView(): void
    {
        $this->assertTrue($this->type->isCustomizedView());
    }

    public function testFormattedValueIsSwatchPlusEscapedName(): void
    {
        $this->colors->method('getSwatchData')->with([7])->willReturn([7 => ['name' => 'Blue', 'hex' => '#0000FF', 'is_active' => true]]);

        $html = $this->type->getFormattedOptionValue('7');

        $this->assertStringContainsString('background-color', $html);
        $this->assertStringContainsString('#0000FF', $html);
        $this->assertStringContainsString('Blue &lt;b&gt;', $html, 'the name is escaped');
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function testFormattedValueWithoutALinkedColorIsJustTheName(): void
    {
        $this->colors->method('getSwatchData')->willReturn([]);

        $this->assertSame('Blue &lt;b&gt;', $this->type->getFormattedOptionValue('7'));
    }

    public function testPrintableValueIsPlainText(): void
    {
        $this->assertSame('Blue <b>', $this->type->getPrintableOptionValue('7'));
    }

    public function testDisabledColorsCannotBeChosen(): void
    {
        $this->colors->method('getSwatchData')->willReturn([7 => ['name' => 'Blue', 'hex' => '#0000FF', 'is_active' => false]]);
        $this->type->setProduct(new \Magento\Framework\DataObject(['store_id' => 1]));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('available colors');
        $this->type->validateUserValue([18 => '7']);
    }
}
