<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Weee;

use Amerimark\FixedLine\Model\FeeResolver;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteAbstractItem;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Weee\Block\Item\Price\Renderer;

/**
 * Weee's renderer derives unit prices as row total / qty, which would show the once-per-line fee spread over
 * each unit (e.g. $1.51 instead of $1.00). When a result was derived from the row, remove the fee share.
 */
class ItemPriceRendererPlugin
{
    private const TOLERANCE = 0.005;

    /**
     * @param FeeResolver $feeResolver
     */
    public function __construct(private readonly FeeResolver $feeResolver)
    {
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetUnitDisplayPriceInclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getRowDisplayPriceInclTax', true, false);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetBaseUnitDisplayPriceInclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getBaseRowDisplayPriceInclTax', true, true);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetUnitDisplayPriceExclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getRowDisplayPriceExclTax', false, false);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetFinalUnitDisplayPriceInclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getFinalRowDisplayPriceInclTax', true, false);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetBaseFinalUnitDisplayPriceInclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getBaseFinalRowDisplayPriceInclTax', true, true);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetFinalUnitDisplayPriceExclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getFinalRowDisplayPriceExclTax', false, false);
    }

    /** @param Renderer $subject @param float $result @return float */
    public function afterGetBaseFinalUnitDisplayPriceExclTax(Renderer $subject, $result)
    {
        return $this->adjust($subject, $result, 'getBaseFinalRowDisplayPriceExclTax', false, true);
    }

    /**
     * Core always divides the order item's base row total by qty ordered here, with or without Weee.
     *
     * @param Renderer $subject
     * @param float $result
     * @return float
     */
    public function afterGetBaseUnitDisplayPriceExclTax(Renderer $subject, $result)
    {
        $item = $subject->getItem();
        $orderItem = ($item instanceof InvoiceItem || $item instanceof CreditmemoItem) ? $item->getOrderItem() : $item;
        $qty = (float)$orderItem->getQtyOrdered();
        if ($qty <= 0.0) {
            return $result;
        }
        $fee = $this->feeResolver->getRowFee($orderItem, false, true);
        $withWeee = $subject->getIncludeWeeeFlag() ? $subject->getBaseRowDisplayPriceExclTax() / $this->unitQty($item) : null;
        if ($withWeee !== null && abs($result - $withWeee) < self::TOLERANCE) {
            // Weee branch: row (document) / document qty
            return $result - $this->feeResolver->getRowFee($item, false, true) / $this->unitQty($item);
        }
        return $fee > 0.0 ? $result - $fee / $qty : $result;
    }

    /**
     * @param Renderer $subject
     * @param float $result
     * @param string $rowMethod
     * @param bool $inclTax
     * @param bool $base
     * @return float
     */
    private function adjust(Renderer $subject, $result, string $rowMethod, bool $inclTax, bool $base)
    {
        $item = $subject->getItem();
        $fee = $this->feeResolver->getRowFee($item, $inclTax, $base);
        if ($fee <= 0.0) {
            return $result;
        }
        $qty = $this->unitQty($item);
        if (abs((float)$result - (float)$subject->$rowMethod() / $qty) < self::TOLERANCE) {
            return $result - $fee / $qty;
        }
        return $result;
    }

    /**
     * Same qty core uses for row -> unit conversion.
     *
     * @param mixed $item
     * @return float
     */
    private function unitQty($item): float
    {
        if ($item instanceof QuoteAbstractItem) {
            $qty = (float)$item->getQty();
        } elseif ($item instanceof OrderItem) {
            $qty = (float)$item->getQtyOrdered();
        } elseif ($item instanceof InvoiceItem || $item instanceof CreditmemoItem) {
            $qty = (float)$item->getQty();
        } else {
            $qty = 1.0;
        }
        return $qty > 0 ? $qty : 1.0;
    }
}
