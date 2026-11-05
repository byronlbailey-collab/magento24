<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Quote\Item;

use Amerimark\FixedLine\Model\FeeCalculator;
use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Adds the per-line fee to the interim row total at the subtotal collector (100).
 *
 * The tax pass recomputes row totals consistently from the fee companion row.
 */
class CalcRowTotalPlugin
{
    /**
     * @param FeeCalculator $feeCalculator
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly FeeCalculator $feeCalculator,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * @param AbstractItem $subject
     * @param AbstractItem $result
     * @return AbstractItem
     */
    public function afterCalcRowTotal(AbstractItem $subject, $result)
    {
        $subject->setData(FeeKeys::CONFIGURED, 0.0);
        $subject->setData(FeeKeys::BASE_CONFIGURED, 0.0);
        $subject->setData(FeeKeys::AMOUNT, 0.0);
        $subject->setData(FeeKeys::BASE_AMOUNT, 0.0);
        $subject->setData(FeeKeys::AMOUNT_INCL_TAX, 0.0);
        $subject->setData(FeeKeys::BASE_AMOUNT_INCL_TAX, 0.0);

        $fees = $this->feeCalculator->getFees($subject);
        if ($fees['base'] <= 0.0) {
            return $result;
        }
        $subject->setData(FeeKeys::CONFIGURED, $fees['quote']);
        $subject->setData(FeeKeys::BASE_CONFIGURED, $fees['base']);
        $subject->setData(FeeKeys::AMOUNT, $fees['quote']);
        $subject->setData(FeeKeys::BASE_AMOUNT, $fees['base']);
        $subject->setData(FeeKeys::AMOUNT_INCL_TAX, $fees['quote']);
        $subject->setData(FeeKeys::BASE_AMOUNT_INCL_TAX, $fees['base']);
        $subject->setBaseRowTotal($this->priceCurrency->round($subject->getBaseRowTotal() + $fees['base']));
        $subject->setRowTotal($this->priceCurrency->round($subject->getRowTotal() + $fees['quote']));
        return $result;
    }
}
