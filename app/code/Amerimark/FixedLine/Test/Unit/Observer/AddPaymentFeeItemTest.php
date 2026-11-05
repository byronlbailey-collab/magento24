<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Observer;

use Amerimark\FixedLine\Observer\AddPaymentFeeItem;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\Cart;
use PHPUnit\Framework\TestCase;

class AddPaymentFeeItemTest extends TestCase
{
    private function observer(object $cart): Observer
    {
        return new Observer(['event' => new Event(['cart' => $cart])]);
    }

    private function salesModel(array $fees): object
    {
        $items = [];
        foreach ($fees as $fee) {
            $items[] = new DataObject(['original_item' => new DataObject(['base_fxline_amount' => $fee])]);
        }
        return new class($items) {
            public function __construct(private array $items)
            {
            }
            public function getAllItems(): array
            {
                return $this->items;
            }
        };
    }

    public function testAddsCustomItemAndRemovesFeeFromImportedSubtotal(): void
    {
        $cart = $this->createMock(Cart::class);
        $cart->method('getSalesModel')->willReturn($this->salesModel([50.0, 0.0, 25.5]));
        $cart->expects($this->once())->method('addSubtotal')->with(-75.5);
        $cart->expects($this->once())->method('addCustomItem')->with($this->anything(), 1, 75.5);
        (new AddPaymentFeeItem())->execute($this->observer($cart));
    }

    public function testNoFeeIsNoOp(): void
    {
        $cart = $this->createMock(Cart::class);
        $cart->method('getSalesModel')->willReturn($this->salesModel([0.0]));
        $cart->expects($this->never())->method('addSubtotal');
        $cart->expects($this->never())->method('addCustomItem');
        (new AddPaymentFeeItem())->execute($this->observer($cart));
    }

    public function testPaypalCartIsSkipped(): void
    {
        $cart = $this->createMock(\Magento\Paypal\Model\Cart::class);
        $cart->expects($this->never())->method('getSalesModel');
        (new AddPaymentFeeItem())->execute($this->observer($cart));
    }
}
