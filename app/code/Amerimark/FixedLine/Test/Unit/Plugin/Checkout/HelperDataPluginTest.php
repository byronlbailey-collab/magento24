<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Checkout;

use Amerimark\FixedLine\Model\FeeResolver;
use Amerimark\FixedLine\Plugin\Checkout\HelperDataPlugin;
use Magento\Checkout\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use PHPUnit\Framework\TestCase;

class HelperDataPluginTest extends TestCase
{
    private function plugin(): HelperDataPlugin
    {
        $currency = $this->createMock(PriceCurrencyInterface::class);
        $currency->method('round')->willReturnCallback(fn ($v) => round((float)$v, 2));
        return new HelperDataPlugin(new FeeResolver($currency), $currency);
    }

    public function testSubtractsFeeShareFromDerivedUnitPrice(): void
    {
        // (149 + 12.29 tax) / 99 = 1.63 derived; fee incl tax 54.12 / 99 = 0.55 -> 1.08
        $item = new DataObject(['qty' => 99, 'base_fxline_amount_incl_tax' => 54.12, 'base_fxline_amount' => 50.0]);
        $this->assertSame(1.08, $this->plugin()->afterGetBasePriceInclTax($this->createMock(Data::class), 1.63, $item));
    }

    public function testItemWithoutFeeIsUnchanged(): void
    {
        $this->assertSame(1.63, $this->plugin()->afterGetBasePriceInclTax($this->createMock(Data::class), 1.63, new DataObject(['qty' => 99])));
    }
}
