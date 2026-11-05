<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Model;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Model\QuoteColorValidator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class QuoteColorValidatorTest extends TestCase
{
    private function quoteWith(string $optionType, string $selection, array $swatchData): array
    {
        $colors = $this->createMock(OptionColors::class);
        $colors->method('getSwatchData')->willReturn($swatchData);

        $option = $this->createPartialMock(Option::class, []);
        $option->setData(['title' => 'Imprint Color', 'type' => $optionType]);
        $product = $this->createPartialMock(Product::class, ['getOptionById']);
        $product->method('getOptionById')->willReturn($option);

        $ids = new \Magento\Framework\DataObject(['value' => '18']);
        $choice = new \Magento\Framework\DataObject(['value' => $selection]);
        $item = $this->createPartialMock(Item::class, ['getOptionByCode', 'getProduct', 'getName', 'getId']);
        $item->method('getOptionByCode')->willReturnCallback(fn ($code) => $code === 'option_ids' ? $ids : $choice);
        $item->method('getProduct')->willReturn($product);
        $item->method('getName')->willReturn('Color Test Product');
        $item->method('getId')->willReturn(5);

        $quote = $this->createPartialMock(Quote::class, ['getAllItems']);
        $quote->method('getAllItems')->willReturn([$item]);

        return [new QuoteColorValidator($colors), $quote];
    }

    public function testAvailableColorIsFine(): void
    {
        [$validator, $quote] = $this->quoteWith('color_swatch', '7', [7 => ['name' => 'Blue', 'hex' => '#00F', 'is_active' => true]]);

        $this->assertSame([], $validator->getProblems($quote));
    }

    public function testDisabledColorIsAProblem(): void
    {
        [$validator, $quote] = $this->quoteWith('color_swatch', '7', [7 => ['name' => 'Blue', 'hex' => '#00F', 'is_active' => false]]);

        $problems = $validator->getProblems($quote);

        $this->assertCount(1, $problems);
        $this->assertStringContainsString('no longer available', (string)$problems[5]['message']);
        $this->assertStringContainsString('Imprint Color', (string)$problems[5]['message']);
    }

    public function testDeletedColorIsAProblem(): void
    {
        [$validator, $quote] = $this->quoteWith('color_swatch', '7', []);

        $this->assertCount(1, $validator->getProblems($quote));
    }

    public function testOtherOptionTypesAreIgnored(): void
    {
        [$validator, $quote] = $this->quoteWith('drop_down', '7', []);

        $this->assertSame([], $validator->getProblems($quote));
    }
}
