<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Plugin\Checkout;

use Amerimark\ColorOption\Plugin\Checkout\MiniCartOptionHtmlPlugin;
use Magento\Checkout\CustomerData\DefaultItem;
use PHPUnit\Framework\TestCase;

class MiniCartOptionHtmlPluginTest extends TestCase
{
    public function testOnlyColorOptionsAreMarkedAsHtml(): void
    {
        $result = ['options' => [
            ['label' => 'Imprint Color', 'option_type' => 'color_swatch'],
            ['label' => 'Size', 'option_type' => 'drop_down'],
        ]];

        $out = (new MiniCartOptionHtmlPlugin())->afterGetItemData($this->createMock(DefaultItem::class), $result);

        $this->assertSame('html', $out['options'][0]['option_type']);
        $this->assertSame('drop_down', $out['options'][1]['option_type']);
    }

    public function testItemsWithoutOptionsPassThrough(): void
    {
        $this->assertSame(['x' => 1], (new MiniCartOptionHtmlPlugin())->afterGetItemData($this->createMock(DefaultItem::class), ['x' => 1]));
    }
}
