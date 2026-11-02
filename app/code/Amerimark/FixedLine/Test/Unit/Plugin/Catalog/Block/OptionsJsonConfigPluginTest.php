<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Catalog\Block;

use Amerimark\FixedLine\Plugin\Catalog\Block\OptionsJsonConfigPlugin;
use Magento\Catalog\Block\Product\View\Options;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class OptionsJsonConfigPluginTest extends TestCase
{
    private function plugin(): OptionsJsonConfigPlugin
    {
        $currency = $this->createMock(PriceCurrencyInterface::class);
        $currency->method('convertAndFormat')->willReturnCallback(fn ($v) => '$' . number_format((float)$v, 2));
        return new OptionsJsonConfigPlugin(new Json(), $currency);
    }

    private function prices(float $amount): array
    {
        return ['prices' => [
            'oldPrice' => ['amount' => $amount, 'adjustments' => []],
            'basePrice' => ['amount' => $amount],
            'finalPrice' => ['amount' => $amount, 'adjustments' => ['tax' => 4.1]],
        ]];
    }

    private function subject(array $options): Options
    {
        $subject = $this->createMock(Options::class);
        $subject->method('getOptions')->willReturn($options);
        return $subject;
    }

    public function testSelectValueIsZeroedAndRenamedWhileFixedIsUntouched(): void
    {
        $fxline = $this->createMock(Value::class);
        $fxline->method('getPriceType')->willReturn('fxline');
        $fxline->method('getData')->with('price')->willReturn(50.0);
        $fixed = $this->createMock(Value::class);
        $fixed->method('getPriceType')->willReturn('fixed');
        $option = $this->createMock(Option::class);
        $option->method('getId')->willReturn(1);
        $option->method('hasValues')->willReturn(true);
        $option->method('getValues')->willReturn([10 => $fxline, 11 => $fixed]);

        $json = json_encode([1 => [
            10 => $this->prices(50.0) + ['type' => 'fxline', 'name' => 'Red'],
            11 => $this->prices(5.0) + ['type' => 'fixed', 'name' => 'Blue'],
        ]]);
        $out = json_decode($this->plugin()->afterGetJsonConfig($this->subject([$option]), $json), true);

        $this->assertSame(0, $out[1][10]['prices']['finalPrice']['amount']);
        $this->assertSame([], $out[1][10]['prices']['finalPrice']['adjustments']);
        $this->assertSame('Red (+$50.00 per line)', $out[1][10]['name']);
        $this->assertEquals(5.0, $out[1][11]['prices']['finalPrice']['amount']);
        $this->assertSame('Blue', $out[1][11]['name']);
    }

    public function testNonSelectFixedLineOptionIsZeroedWithoutRename(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getId')->willReturn(2);
        $option->method('hasValues')->willReturn(false);
        $option->method('getPriceType')->willReturn('fxline');
        $option->method('getData')->with('price')->willReturn(50.0);
        $json = json_encode([2 => $this->prices(50.0) + ['type' => 'fxline', 'name' => 'Engraving']]);
        $out = json_decode($this->plugin()->afterGetJsonConfig($this->subject([$option]), $json), true);
        $this->assertSame(0, $out[2]['prices']['basePrice']['amount']);
        $this->assertSame('Engraving', $out[2]['name']);
    }

    public function testConfigWithoutFixedLineIsReturnedUnchanged(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getId')->willReturn(3);
        $option->method('hasValues')->willReturn(false);
        $option->method('getPriceType')->willReturn('fixed');
        $json = json_encode([3 => $this->prices(9.0)]);
        $this->assertSame($json, $this->plugin()->afterGetJsonConfig($this->subject([$option]), $json));
    }
}
