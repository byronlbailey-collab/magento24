<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Tax;

use Amerimark\FixedLine\Plugin\Tax\MergeFeeDetailsPlugin;
use Magento\Tax\Api\Data\AppliedTaxInterface;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Model\TaxDetails\ItemDetails;
use Magento\Tax\Api\TaxCalculationInterface;
use PHPUnit\Framework\TestCase;

class MergeFeeDetailsPluginTest extends TestCase
{
    private function quoteDetails(array $types): QuoteDetailsInterface
    {
        $items = [];
        foreach ($types as $type) {
            $item = $this->createMock(QuoteDetailsItemInterface::class);
            $item->method('getType')->willReturn($type);
            $items[] = $item;
        }
        $details = $this->createMock(QuoteDetailsInterface::class);
        $details->method('getItems')->willReturn($items);
        return $details;
    }

    public function testNoFeeRowIsStrictNoOp(): void
    {
        $result = $this->createMock(TaxDetailsInterface::class);
        $result->expects($this->never())->method('setItems');
        $out = (new MergeFeeDetailsPlugin())->afterCalculateTax(
            $this->createMock(TaxCalculationInterface::class),
            $result,
            $this->quoteDetails(['product', 'weee'])
        );
        $this->assertSame($result, $out);
    }

    public function testFeeRowIsFoldedIntoProductAndRemoved(): void
    {
        $product = $this->createMock(ItemDetails::class);
        $product->method('getType')->willReturn('product');
        $product->method('getRowTotal')->willReturn(99.0);
        $product->method('getRowTotalInclTax')->willReturn(107.17);
        $product->method('getRowTax')->willReturn(8.17);
        $product->method('getDiscountTaxCompensationAmount')->willReturn(0.0);
        $product->method('getTaxableAmount')->willReturn(null);
        $product->method('getAppliedTaxes')->willReturn([]);

        $feeTax = $this->createMock(AppliedTaxInterface::class);
        $fee = $this->createMock(ItemDetails::class);
        $fee->method('getType')->willReturn('fxline');
        $fee->method('getAssociatedItemCode')->willReturn('p1');
        $fee->method('getRowTotal')->willReturn(50.0);
        $fee->method('getRowTotalInclTax')->willReturn(54.12);
        $fee->method('getRowTax')->willReturn(4.12);
        $fee->method('getDiscountTaxCompensationAmount')->willReturn(0.0);
        $fee->method('getTaxableAmount')->willReturn(null);
        $fee->method('getAppliedTaxes')->willReturn(['US-CA' => $feeTax]);

        $product->expects($this->once())->method('setRowTotal')->with(149.0);
        $product->expects($this->once())->method('setRowTotalInclTax')->with(161.29);
        $product->expects($this->once())->method('setRowTax')->with(12.29);
        $product->expects($this->once())->method('setAppliedTaxes')->with(['US-CA' => $feeTax]);

        $result = $this->createMock(TaxDetailsInterface::class);
        $result->method('getItems')->willReturn(['p1' => $product, 'fxline-p1' => $fee]);
        $result->expects($this->once())->method('setItems')->with(['p1' => $product]);

        (new MergeFeeDetailsPlugin())->afterCalculateTax(
            $this->createMock(TaxCalculationInterface::class),
            $result,
            $this->quoteDetails(['product', 'fxline'])
        );
    }
}
