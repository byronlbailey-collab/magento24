<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\SalesRule;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule\Action\Discount\BuyXGetY;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Buy-X-Get-Y free units must not carry the once-per-line fee: temporarily remove fee/qty from the discount price.
 */
class BuyXGetYExcludeFeePlugin
{
    /**
     * @param TaxConfig $taxConfig
     */
    public function __construct(private readonly TaxConfig $taxConfig)
    {
    }

    /**
     * @param BuyXGetY $subject
     * @param callable $proceed
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     * @return mixed
     */
    public function aroundCalculate(BuyXGetY $subject, callable $proceed, $rule, $item, $qty)
    {
        $totalQty = (float)$item->getTotalQty();
        $fee = (float)$item->getData(FeeKeys::AMOUNT);
        $baseFee = (float)$item->getData(FeeKeys::BASE_AMOUNT);
        if (($fee <= 0.0 && $baseFee <= 0.0) || $totalQty <= 0.0) {
            return $proceed($rule, $item, $qty);
        }
        $inclTax = $this->taxConfig->discountTax($item->getStore());
        $feeBasis = (float)$item->getData($inclTax ? FeeKeys::AMOUNT_INCL_TAX : FeeKeys::AMOUNT) / $totalQty;
        $baseFeeBasis = (float)$item->getData(
            $inclTax ? FeeKeys::BASE_AMOUNT_INCL_TAX : FeeKeys::BASE_AMOUNT
        ) / $totalQty;

        $origPrice = $item->getDiscountCalculationPrice();
        $origBasePrice = $item->getBaseDiscountCalculationPrice();
        $item->setDiscountCalculationPrice((float)$origPrice - $feeBasis);
        $item->setBaseDiscountCalculationPrice((float)$origBasePrice - $baseFeeBasis);
        try {
            return $proceed($rule, $item, $qty);
        } finally {
            $item->setDiscountCalculationPrice($origPrice);
            $item->setBaseDiscountCalculationPrice($origBasePrice);
        }
    }
}
