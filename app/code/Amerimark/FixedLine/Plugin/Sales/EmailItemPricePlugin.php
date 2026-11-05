<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Sales;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Sales\Block\Order\Email\Items\DefaultItems;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Renders email item prices from the real document row total for lines carrying a FixedLine fee.
 *
 * Core overwrites row_total with unit price * document qty (and mutates the item), which drops the fee.
 */
class EmailItemPricePlugin
{
    /**
     * @param DefaultItems $subject
     * @param callable $proceed
     * @param OrderItem|\Magento\Sales\Model\Order\Invoice\Item|\Magento\Sales\Model\Order\Creditmemo\Item $item
     * @return string
     */
    public function aroundGetItemPrice(DefaultItems $subject, callable $proceed, $item)
    {
        $orderItem = $item instanceof OrderItem ? $item : $item->getOrderItem();
        if (!$orderItem || (float)$orderItem->getData(FeeKeys::AMOUNT) <= 0.0) {
            return $proceed($item);
        }
        $block = $subject->getLayout()->getBlock('item_price');
        if (!$block) {
            return $proceed($item);
        }

        $render = clone $item;
        $document = $subject->getItem();
        if ($item instanceof OrderItem && $document !== $item && $document->getRowTotal() !== null) {
            // Invoice email passes the order item; show the invoice's own (pro rata) row amounts.
            $render->setRowTotal($document->getRowTotal());
            $render->setBaseRowTotal($document->getBaseRowTotal());
            $render->setRowTotalInclTax($document->getRowTotalInclTax());
            $render->setBaseRowTotalInclTax($document->getBaseRowTotalInclTax());
        }
        $block->setItem($render);
        return $block->toHtml();
    }
}
