<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Quote\Item;

use Amerimark\FixedLine\Plugin\Quote\Item\ToOrderItemPlugin;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use PHPUnit\Framework\TestCase;

class ToOrderItemPluginTest extends TestCase
{
    public function testCopiesFeeColumns(): void
    {
        $quoteItem = new DataObject([
            'fxline_amount' => 50.0,
            'base_fxline_amount' => 50.0,
            'fxline_amount_incl_tax' => 54.12,
            'base_fxline_amount_incl_tax' => 54.12,
        ]);
        $orderItem = new DataObject();
        (new ToOrderItemPlugin())->afterConvert($this->createMock(ToOrderItem::class), $orderItem, $quoteItem);
        $this->assertSame(50.0, $orderItem->getData('fxline_amount'));
        $this->assertSame(54.12, $orderItem->getData('base_fxline_amount_incl_tax'));
    }

    public function testItemWithoutFeeLeavesOrderItemUntouched(): void
    {
        $orderItem = new DataObject();
        (new ToOrderItemPlugin())->afterConvert($this->createMock(ToOrderItem::class), $orderItem, new DataObject());
        $this->assertSame([], $orderItem->getData());
    }
}
