<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Catalog\Pricing;

use Amerimark\FixedLine\Plugin\Catalog\OptionRegularPricePlugin;
use Amerimark\FixedLine\Plugin\Catalog\Pricing\CustomOptionPriceCalculatorPlugin;
use Amerimark\FixedLine\Plugin\Catalog\Pricing\CustomOptionPricePlugin;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Pricing\Price\CustomOptionPrice;
use Magento\Catalog\Pricing\Price\CustomOptionPriceCalculator;
use PHPUnit\Framework\TestCase;

class PricingPluginsTest extends TestCase
{
    private function value(string $type): Value
    {
        $v = $this->createMock(Value::class);
        $v->method('getPriceType')->willReturn($type);
        return $v;
    }

    public function testCalculatorReturnsZeroForFixedLineAndProceedsOtherwise(): void
    {
        $plugin = new CustomOptionPriceCalculatorPlugin();
        $subject = $this->createMock(CustomOptionPriceCalculator::class);
        $this->assertSame(0.0, $plugin->aroundGetOptionPriceByPriceCode($subject, fn () => 99.0, $this->value('fxline')));
        $this->assertSame(99.0, $plugin->aroundGetOptionPriceByPriceCode($subject, fn () => 99.0, $this->value('fixed')));
        $this->assertSame(99.0, $plugin->aroundGetOptionPriceByPriceCode($subject, fn () => 99.0, $this->value('percent')));
    }

    public function testOptionRegularPrice(): void
    {
        $plugin = new OptionRegularPricePlugin();
        $fx = $this->createMock(Option::class);
        $fx->method('getPriceType')->willReturn('fxline');
        $fixed = $this->createMock(Option::class);
        $fixed->method('getPriceType')->willReturn('fixed');
        $this->assertSame(0.0, $plugin->aroundGetRegularPrice($fx, fn () => 50.0));
        $this->assertSame(50.0, $plugin->aroundGetRegularPrice($fixed, fn () => 50.0));
    }

    public function testRangeZeroesOnlyNonSelectFixedLineOptions(): void
    {
        $fxText = $this->createMock(Option::class);
        $fxText->method('getGroupByType')->willReturn(Option::OPTION_GROUP_TEXT);
        $fxText->method('getPriceType')->willReturn('fxline');
        $fixedText = $this->createMock(Option::class);
        $fixedText->method('getGroupByType')->willReturn(Option::OPTION_GROUP_TEXT);
        $fixedText->method('getPriceType')->willReturn('fixed');
        $product = $this->createMock(Product::class);
        $product->method('getOptionById')->willReturnMap([[1, $fxText], [2, $fixedText]]);
        $subject = $this->createMock(CustomOptionPrice::class);
        $subject->method('getProduct')->willReturn($product);

        $result = (new CustomOptionPricePlugin())->afterGetValue($subject, [
            ['option_id' => 1, 'type' => 'field', 'min' => 50.0, 'max' => 50.0],
            ['option_id' => 2, 'type' => 'field', 'min' => 5.0, 'max' => 5.0],
        ]);
        $this->assertSame([0.0, 0.0], [$result[0]['min'], $result[0]['max']]);
        $this->assertSame([5.0, 5.0], [$result[1]['min'], $result[1]['max']]);
    }
}
