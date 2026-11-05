<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\SalesRule;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule\Action\Discount\Data;
use Magento\SalesRule\Model\Utility;

/**
 * Removes float noise (e.g. 19.99*7+50 = 189.92999999999998) from clamped discounts on fee lines.
 */
class MinFixRoundPlugin
{
    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(private readonly PriceCurrencyInterface $priceCurrency)
    {
    }

    /**
     * @param Utility $subject
     * @param mixed $result
     * @param Data $discountData
     * @param AbstractItem $item
     * @return mixed
     */
    public function afterMinFix(Utility $subject, $result, Data $discountData, AbstractItem $item)
    {
        if ((float)$item->getData(\Amerimark\FixedLine\Model\FeeKeys::BASE_AMOUNT) > 0.0) {
            $discountData->setAmount($this->priceCurrency->round($discountData->getAmount()));
            $discountData->setBaseAmount($this->priceCurrency->round($discountData->getBaseAmount()));
        }
        return $result;
    }
}
