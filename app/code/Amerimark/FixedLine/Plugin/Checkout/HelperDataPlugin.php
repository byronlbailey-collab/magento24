<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Checkout;

use Amerimark\FixedLine\Model\FeeResolver;
use Magento\Checkout\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * getBasePriceInclTax derives the unit price as (base row total + tax) / qty; remove the per-line fee share.
 */
class HelperDataPlugin
{
    /**
     * @param FeeResolver $feeResolver
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly FeeResolver $feeResolver,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * @param Data $subject
     * @param float $result
     * @param mixed $item
     * @return float
     */
    public function afterGetBasePriceInclTax(Data $subject, $result, $item)
    {
        $fee = $this->feeResolver->getRowFee($item, true, true);
        if ($fee <= 0.0) {
            return $result;
        }
        $qty = (float)($item->getQty() ?: ($item->getQtyOrdered() ?: 1));
        return $qty > 0 ? (float)$this->priceCurrency->round($result - $fee / $qty) : $result;
    }
}
