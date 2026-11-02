<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Model;

use Amerimark\FixedLine\Model\FeeCalculator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class FeeCalculatorTest extends TestCase
{
    private function calculator(): FeeCalculator
    {
        $currency = $this->createMock(PriceCurrencyInterface::class);
        $currency->method('round')->willReturnCallback(fn ($v) => round((float)$v, 2));
        $currency->method('convert')->willReturnCallback(fn ($v) => (float)$v * 2);
        return new FeeCalculator($currency);
    }

    private function codeOption(string $value): object
    {
        return new class($value) {
            public function __construct(private string $v)
            {
            }
            public function getValue(): string
            {
                return $this->v;
            }
        };
    }

    private function item(array $options, array $selections, array $over = []): Item
    {
        $product = $this->createMock(Product::class);
        $product->method('getOptionById')->willReturnCallback(fn ($id) => $options[$id] ?? null);
        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getTotalQty')->willReturn($over['qty'] ?? 99);
        // getCustomPrice/getOriginalCustomPrice/getHasChildren are magic (__call) in Magento DataObjects.
        $item->method('__call')->willReturnCallback(
            fn ($method) => $method === 'getCustomPrice' ? ($over['custom'] ?? null) : null
        );
        $item->method('getParentItem')->willReturn($over['parent'] ?? null);
        $item->method('getOptionByCode')->willReturnCallback(
            fn ($code) => isset($selections[$code]) ? $this->codeOption($selections[$code]) : null
        );
        return $item;
    }

    private function valueOption(string $group, string $priceType, float $price, array $values = []): Option
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn($group);
        $option->method('getPriceType')->willReturn($priceType);
        $option->method('getData')->with('price')->willReturn($price);
        $option->method('getValueById')->willReturnCallback(fn ($id) => $values[$id] ?? null);
        return $option;
    }

    private function value(string $type, float $price): Value
    {
        $value = $this->createMock(Value::class);
        $value->method('getPriceType')->willReturn($type);
        $value->method('getData')->with('price')->willReturn($price);
        return $value;
    }

    public function testNonSelectFixedLineFeeIsIndependentOfQty(): void
    {
        $opt = $this->valueOption(Option::OPTION_GROUP_TEXT, 'fxline', 50.0);
        foreach ([1, 99] as $qty) {
            $fees = $this->calculator()->getFees($this->item([1 => $opt], ['option_ids' => '1', 'option_1' => 'abc'], ['qty' => $qty]));
            $this->assertSame(['base' => 50.0, 'quote' => 100.0], $fees);
        }
    }

    public function testSelectSumsOnlyFixedLineValues(): void
    {
        $opt = $this->valueOption(Option::OPTION_GROUP_SELECT, 'fixed', 0.0, [
            '1' => $this->value('fxline', 10.0),
            '2' => $this->value('fixed', 99.0),
            '3' => $this->value('fxline', 5.5),
            '4' => $this->value('percent', 20.0),
        ]);
        $fees = $this->calculator()->getFees($this->item([7 => $opt], ['option_ids' => '7', 'option_7' => '1,2,3,4']));
        $this->assertSame(15.5, $fees['base']);
    }

    public function testPlainFixedAndPercentCarryNoFee(): void
    {
        $opt = $this->valueOption(Option::OPTION_GROUP_TEXT, 'fixed', 50.0);
        $fees = $this->calculator()->getFees($this->item([1 => $opt], ['option_ids' => '1', 'option_1' => 'abc']));
        $this->assertSame(0.0, $fees['base']);
    }

    public function testCustomPriceWaivesFee(): void
    {
        $opt = $this->valueOption(Option::OPTION_GROUP_TEXT, 'fxline', 50.0);
        $fees = $this->calculator()->getFees($this->item([1 => $opt], ['option_ids' => '1', 'option_1' => 'abc'], ['custom' => 9.99]));
        $this->assertSame(0.0, $fees['base']);
    }

    public function testChildItemCarriesNoFee(): void
    {
        $opt = $this->valueOption(Option::OPTION_GROUP_TEXT, 'fxline', 50.0);
        $fees = $this->calculator()->getFees(
            $this->item([1 => $opt], ['option_ids' => '1', 'option_1' => 'abc'], ['parent' => $this->createMock(Item::class)])
        );
        $this->assertSame(0.0, $fees['base']);
    }
}
