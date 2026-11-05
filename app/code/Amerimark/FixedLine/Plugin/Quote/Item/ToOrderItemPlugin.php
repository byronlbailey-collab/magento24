<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Quote\Item;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Copies the persisted FixedLine fee columns from the quote item to the order item.
 *
 * Uses setData because the order item has no real setters for these columns, so a fieldset entry would be dropped.
 * Money fields (price, row_total, tax_amount, discount_amount...) are already copied by core's fieldset, which is
 * why invoices and credit memos split the fee pro rata with no further changes.
 */
class ToOrderItemPlugin
{
    private const COLUMNS = [
        FeeKeys::AMOUNT,
        FeeKeys::BASE_AMOUNT,
        FeeKeys::AMOUNT_INCL_TAX,
        FeeKeys::BASE_AMOUNT_INCL_TAX,
    ];

    /**
     * @param ToOrderItem $subject
     * @param OrderItem $result
     * @param AbstractItem $item
     * @return OrderItem
     */
    public function afterConvert(ToOrderItem $subject, $result, $item)
    {
        foreach (self::COLUMNS as $column) {
            $value = $item->getData($column);
            if ($value !== null) {
                $result->setData($column, $value);
            }
        }
        return $result;
    }
}
