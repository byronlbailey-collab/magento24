<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Tax;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\TaxCalculationInterface;

/**
 * Folds the fee companion row into its product row after tax calculation and drops the companion.
 *
 * Top-level subtotal, tax and applied taxes were already aggregated by core including the fee.
 * Strict no-op when the quote details contain no fee row.
 */
class MergeFeeDetailsPlugin
{
    /**
     * @param TaxCalculationInterface $subject
     * @param TaxDetailsInterface $result
     * @param QuoteDetailsInterface $quoteDetails
     * @return TaxDetailsInterface
     */
    public function afterCalculateTax(
        TaxCalculationInterface $subject,
        $result,
        QuoteDetailsInterface $quoteDetails
    ) {
        $hasFee = false;
        foreach ((array)$quoteDetails->getItems() as $item) {
            if ($item->getType() === FeeKeys::TAX_ITEM_TYPE) {
                $hasFee = true;
                break;
            }
        }
        if (!$hasFee) {
            return $result;
        }

        $items = $result->getItems();
        foreach ($items as $code => $feeRow) {
            if ($feeRow->getType() !== FeeKeys::TAX_ITEM_TYPE) {
                continue;
            }
            $productCode = $feeRow->getAssociatedItemCode();
            if (!isset($items[$productCode])) {
                continue;
            }
            $product = $items[$productCode];
            $product->setRowTotal($product->getRowTotal() + $feeRow->getRowTotal());
            $product->setRowTotalInclTax($product->getRowTotalInclTax() + $feeRow->getRowTotalInclTax());
            $product->setRowTax($product->getRowTax() + $feeRow->getRowTax());
            $product->setDiscountTaxCompensationAmount(
                $product->getDiscountTaxCompensationAmount() + $feeRow->getDiscountTaxCompensationAmount()
            );
            if ($feeRow->getTaxableAmount() !== null) {
                $product->setTaxableAmount((float)$product->getTaxableAmount() + $feeRow->getTaxableAmount());
            }
            $product->setAppliedTaxes(
                $this->mergeAppliedTaxes((array)$product->getAppliedTaxes(), (array)$feeRow->getAppliedTaxes())
            );
            $product->setData(FeeKeys::DETAILS_ROW_TOTAL, (float)$feeRow->getRowTotal());
            $product->setData(FeeKeys::DETAILS_ROW_TOTAL_INCL_TAX, (float)$feeRow->getRowTotalInclTax());
            unset($items[$code]);
        }
        $result->setItems($items);
        return $result;
    }

    /**
     * Merge applied taxes by rate key, summing amounts.
     *
     * @param array $productTaxes
     * @param array $feeTaxes
     * @return array
     */
    private function mergeAppliedTaxes(array $productTaxes, array $feeTaxes): array
    {
        foreach ($feeTaxes as $key => $feeTax) {
            if (isset($productTaxes[$key])) {
                $productTaxes[$key]->setAmount($productTaxes[$key]->getAmount() + $feeTax->getAmount());
            } else {
                $productTaxes[$key] = $feeTax;
            }
        }
        return $productTaxes;
    }
}
