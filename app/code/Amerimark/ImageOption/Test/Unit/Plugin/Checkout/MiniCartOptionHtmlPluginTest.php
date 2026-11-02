<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Plugin\Checkout;

use Amerimark\ImageOption\Plugin\Checkout\MiniCartOptionHtmlPlugin;
use Magento\Checkout\CustomerData\DefaultItem;
use PHPUnit\Framework\TestCase;

class MiniCartOptionHtmlPluginTest extends TestCase
{
    public function testOnlyImageGroupOptionsAreReportedAsHtml(): void
    {
        $result = (new MiniCartOptionHtmlPlugin())->afterGetItemData($this->createMock(DefaultItem::class), [
            'options' => [
                ['label' => 'Color', 'option_type' => 'drop_down', 'value' => 'Red'],
                ['label' => 'Logo', 'option_type' => 'image_group', 'value' => '<span>x</span>'],
            ],
        ]);
        $this->assertSame('drop_down', $result['options'][0]['option_type']);
        $this->assertSame('html', $result['options'][1]['option_type']);
    }

    public function testItemWithoutOptionsIsUntouched(): void
    {
        $this->assertSame(['qty' => 1], (new MiniCartOptionHtmlPlugin())->afterGetItemData($this->createMock(DefaultItem::class), ['qty' => 1]));
    }
}
