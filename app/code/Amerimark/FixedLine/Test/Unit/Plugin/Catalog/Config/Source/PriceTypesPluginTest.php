<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Catalog\Config\Source;

use Amerimark\FixedLine\Model\PriceType;
use Amerimark\FixedLine\Plugin\Catalog\Config\Source\PriceTypesPlugin;
use Magento\Catalog\Model\Config\Source\Product\Options\Price;
use PHPUnit\Framework\TestCase;

class PriceTypesPluginTest extends TestCase
{
    public function testCodeFitsCoreColumn(): void
    {
        $this->assertLessThanOrEqual(7, strlen(PriceType::CODE));
    }

    public function testAddsFixedLineToOptionArray(): void
    {
        $plugin = new PriceTypesPlugin();
        $result = $plugin->afterToOptionArray(
            $this->createMock(Price::class),
            [['value' => 'fixed', 'label' => 'Fixed'], ['value' => 'percent', 'label' => 'Percent']]
        );
        $this->assertSame(['fixed', 'percent', PriceType::CODE], array_column($result, 'value'));
    }

    public function testAddsFixedLineCurrencyPrefix(): void
    {
        $plugin = new PriceTypesPlugin();
        $result = $plugin->afterPrefixesToOptionArray(
            $this->createMock(Price::class),
            [['value' => 'fixed', 'label' => '$'], ['value' => 'percent', 'label' => '%']]
        );
        $this->assertSame(['value' => PriceType::CODE, 'label' => '$'], end($result));
    }
}
