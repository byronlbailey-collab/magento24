<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Sales;

use Amerimark\FixedLine\Plugin\Sales\EmailItemPricePlugin;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Order\Email\Items\DefaultItems;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class EmailItemPricePluginTest extends TestCase
{
    private function subject(object $document, object $block): DefaultItems
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->with('item_price')->willReturn($block);
        $subject = $this->createMock(DefaultItems::class);
        $subject->method('getLayout')->willReturn($layout);
        // getItem() is magic (__call) on blocks.
        $subject->method('__call')->willReturnCallback(fn ($method) => $method === 'getItem' ? $document : null);
        return $subject;
    }

    public function testInvoiceEmailRendersDocumentRowTotalOnAClone(): void
    {
        $orderItem = $this->createPartialMock(OrderItem::class, []);
        $orderItem->setData(['fxline_amount' => 50.0, 'row_total' => 149.0, 'row_total_incl_tax' => 161.29]);
        $invoiceItem = $this->createPartialMock(InvoiceItem::class, []);
        $invoiceItem->setData(['row_total' => 49.67, 'base_row_total' => 49.67, 'row_total_incl_tax' => 53.76, 'base_row_total_incl_tax' => 53.76]);

        $captured = null;
        $block = $this->getMockBuilder(AbstractBlock::class)->disableOriginalConstructor()->onlyMethods(['toHtml', '__call'])->getMock();
        $block->method('toHtml')->willReturn('<html>');
        $block->method('__call')->willReturnCallback(function ($method, $args) use (&$captured, $block) {
            if ($method === 'setItem') {
                $captured = $args[0];
            }
            return $block;
        });

        $result = (new EmailItemPricePlugin())->aroundGetItemPrice(
            $this->subject($invoiceItem, $block),
            fn () => 'proceeded',
            $orderItem
        );
        $this->assertSame('<html>', $result);
        $this->assertNotSame($orderItem, $captured);
        $this->assertSame(49.67, $captured->getRowTotal());
        $this->assertSame(53.76, $captured->getRowTotalInclTax());
        $this->assertSame(149.0, $orderItem->getRowTotal(), 'order item must not be mutated');
    }

    public function testItemWithoutFeeProceeds(): void
    {
        $orderItem = $this->createPartialMock(OrderItem::class, []);
        $result = (new EmailItemPricePlugin())->aroundGetItemPrice(
            $this->subject($orderItem, $this->createMock(AbstractBlock::class)),
            fn ($item) => 'proceeded',
            $orderItem
        );
        $this->assertSame('proceeded', $result);
    }
}
