<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\ViewModel;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Model\SwatchStyles;
use Amerimark\ColorOption\ViewModel\Swatches;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class SwatchesTest extends TestCase
{
    private function styles(): SwatchStyles
    {
        return new SwatchStyles(['spectrum' => ['label' => 'Full spectrum', 'image' => 'conic-gradient(red, blue)']]);
    }

    private function value(int $id): DataObject
    {
        return new class(['option_type_id' => $id]) extends DataObject {
            public function getOptionTypeId() { return $this->getData('option_type_id'); }
        };
    }

    public function testDisabledAndUnlinkedColorsAreLeftOutInDisplayOrder(): void
    {
        $colors = $this->createMock(OptionColors::class);
        $colors->method('getSwatchData')->with([3, 4, 5, 6])->willReturn([
            3 => ['name' => 'Red', 'hex' => '#FF0000', 'swatch_style' => 'solid', 'is_active' => true],
            4 => ['name' => 'Gone', 'hex' => '#111111', 'swatch_style' => 'solid', 'is_active' => false],
            6 => ['name' => 'Blue', 'hex' => '#0000FF', 'swatch_style' => 'spectrum', 'is_active' => true],
        ]);
        $option = $this->createPartialMock(Option::class, ['getValues']);
        $option->method('getValues')->willReturn([$this->value(3), $this->value(4), $this->value(5), $this->value(6)]);

        $swatches = (new Swatches($colors, $this->styles()))->getSwatches($option);

        $this->assertSame(['Red', 'Blue'], array_column($swatches, 'name'));
        $this->assertSame([3, 6], array_column($swatches, 'value'));
        $this->assertSame('#0000FF', $swatches[1]['hex']);
        $this->assertSame('background-color:#FF0000', $swatches[0]['css']);
        $this->assertSame('background-color:#0000FF;background-image:conic-gradient(red, blue)', $swatches[1]['css']);
    }

    public function testOptionWithoutValuesHasNoSwatches(): void
    {
        $option = $this->createPartialMock(Option::class, ['getValues']);
        $option->method('getValues')->willReturn(null);

        $this->assertSame([], (new Swatches($this->createMock(OptionColors::class), $this->styles()))->getSwatches($option));
    }
}
