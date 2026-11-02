<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Model;

use Amerimark\FixedLine\Model\FeeResolver;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class FeeResolverTest extends TestCase
{
    private function resolver(): FeeResolver
    {
        $currency = $this->createMock(PriceCurrencyInterface::class);
        $currency->method('round')->willReturnCallback(fn ($v) => round((float)$v, 2));
        return new FeeResolver($currency);
    }

    public function testQuoteAndOrderItemsReadColumns(): void
    {
        $item = new DataObject(['fxline_amount' => 50.0, 'fxline_amount_incl_tax' => 54.12, 'base_fxline_amount' => 49.0]);
        $this->assertSame(50.0, $this->resolver()->getRowFee($item));
        $this->assertSame(54.12, $this->resolver()->getRowFee($item, true));
        $this->assertSame(49.0, $this->resolver()->getRowFee($item, false, true));
        $this->assertSame(0.0, $this->resolver()->getRowFee(new DataObject()));
    }

    public function testInvoiceItemFeeIsRowTotalMinusUnitTimesQty(): void
    {
        $orderItem = $this->createPartialMock(OrderItem::class, []);
        $orderItem->setData(['fxline_amount' => 50.0, 'fxline_amount_incl_tax' => 54.12, 'qty_ordered' => 99.0]);
        $invoiceItem = $this->createPartialMock(InvoiceItem::class, ['getOrderItem']);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        $invoiceItem->setData(['row_total' => 49.67, 'price' => 1.0, 'qty' => 33.0]);
        $this->assertSame(16.67, $this->resolver()->getRowFee($invoiceItem));
        $this->assertSame(18.04, $this->resolver()->getRowFee($invoiceItem, true));
    }

    public function testInvoiceItemWithoutFeeIsZero(): void
    {
        $orderItem = $this->createPartialMock(OrderItem::class, []);
        $invoiceItem = $this->createPartialMock(InvoiceItem::class, ['getOrderItem']);
        $invoiceItem->method('getOrderItem')->willReturn($orderItem);
        $this->assertSame(0.0, $this->resolver()->getRowFee($invoiceItem));
    }
}
