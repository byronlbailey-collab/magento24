<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;

/**
 * Resolves the FixedLine fee contained in a quote, order, invoice or credit memo item row.
 */
class FeeResolver
{
    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(private readonly PriceCurrencyInterface $priceCurrency)
    {
    }

    /**
     * Get the fee contained in the item's row (0 when none).
     *
     * @param \Magento\Framework\DataObject $item quote, order, invoice or credit memo item
     * @param bool $inclTax
     * @param bool $base
     * @return float
     */
    public function getRowFee($item, bool $inclTax = false, bool $base = false): float
    {
        if ($item instanceof InvoiceItem || $item instanceof CreditmemoItem) {
            return $this->getDocumentRowFee($item, $inclTax, $base);
        }
        return (float)$item->getData($this->key($inclTax, $base));
    }

    /**
     * Document item: excl tax = row_total - round(unit price) * qty; incl tax is pro rata of the order item's.
     *
     * @param InvoiceItem|CreditmemoItem $item
     * @param bool $inclTax
     * @param bool $base
     * @return float
     */
    private function getDocumentRowFee($item, bool $inclTax, bool $base): float
    {
        $orderItem = $item->getOrderItem();
        if (!$orderItem || (float)$orderItem->getData($this->key(false, false)) <= 0.0) {
            return 0.0;
        }
        if ($inclTax) {
            $ordered = (float)$orderItem->getQtyOrdered();
            return $ordered > 0
                ? (float)$this->priceCurrency->round(
                    (float)$orderItem->getData($this->key(true, $base)) * (float)$item->getQty() / $ordered
                )
                : 0.0;
        }
        $rowTotal = (float)($base ? $item->getBaseRowTotal() : $item->getRowTotal());
        $unit = (float)($base ? $item->getBasePrice() : $item->getPrice());
        return max(0.0, (float)$this->priceCurrency->round($rowTotal - $this->priceCurrency->round($unit) * (float)$item->getQty()));
    }

    /**
     * @param bool $inclTax
     * @param bool $base
     * @return string
     */
    private function key(bool $inclTax, bool $base): string
    {
        if ($inclTax) {
            return $base ? FeeKeys::BASE_AMOUNT_INCL_TAX : FeeKeys::AMOUNT_INCL_TAX;
        }
        return $base ? FeeKeys::BASE_AMOUNT : FeeKeys::AMOUNT;
    }
}
