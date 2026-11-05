<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Tax;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * Persists fee-only amounts on the quote item and makes discounts see the fee-inclusive row.
 *
 * SalesRule reads only discount_calculation_price, so re-stamping it as row/qty covers percent,
 * cart-fixed and the discount clamp without touching any SalesRule getter.
 */
class UpdateItemFeeInfoPlugin
{
    /**
     * @param TaxConfig $taxConfig
     */
    public function __construct(private readonly TaxConfig $taxConfig)
    {
    }

    /**
     * @param CommonTaxCollector $subject
     * @param CommonTaxCollector $result
     * @param AbstractItem $quoteItem
     * @param TaxDetailsItemInterface $itemTaxDetails
     * @param TaxDetailsItemInterface $baseItemTaxDetails
     * @param \Magento\Store\Model\Store $store
     * @return CommonTaxCollector
     */
    public function afterUpdateItemTaxInfo(
        CommonTaxCollector $subject,
        $result,
        $quoteItem,
        $itemTaxDetails,
        $baseItemTaxDetails,
        $store
    ) {
        $fee = (float)$itemTaxDetails->getData(FeeKeys::DETAILS_ROW_TOTAL);
        $feeIncl = (float)$itemTaxDetails->getData(FeeKeys::DETAILS_ROW_TOTAL_INCL_TAX);
        $baseFee = (float)$baseItemTaxDetails->getData(FeeKeys::DETAILS_ROW_TOTAL);
        $baseFeeIncl = (float)$baseItemTaxDetails->getData(FeeKeys::DETAILS_ROW_TOTAL_INCL_TAX);

        $quoteItem->setData(FeeKeys::AMOUNT, $fee);
        $quoteItem->setData(FeeKeys::AMOUNT_INCL_TAX, $feeIncl);
        $quoteItem->setData(FeeKeys::BASE_AMOUNT, $baseFee);
        $quoteItem->setData(FeeKeys::BASE_AMOUNT_INCL_TAX, $baseFeeIncl);

        $qty = (float)$quoteItem->getTotalQty();
        if (($fee <= 0.0 && $baseFee <= 0.0) || $qty <= 0.0 || $quoteItem->getParentItem()) {
            return $result;
        }
        if ($this->taxConfig->discountTax($store)) {
            $quoteItem->setDiscountCalculationPrice((float)$quoteItem->getRowTotalInclTax() / $qty);
            $quoteItem->setBaseDiscountCalculationPrice((float)$quoteItem->getBaseRowTotalInclTax() / $qty);
        } else {
            $quoteItem->setDiscountCalculationPrice((float)$quoteItem->getRowTotal() / $qty);
            $quoteItem->setBaseDiscountCalculationPrice((float)$quoteItem->getBaseRowTotal() / $qty);
        }
        return $result;
    }
}
