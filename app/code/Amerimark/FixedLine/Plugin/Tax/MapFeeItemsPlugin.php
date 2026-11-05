<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Tax;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * Adds a qty-1 companion tax row carrying the per-line fee after each product row that has one.
 *
 * The companion shares the product's tax class and is merged back into the product row after tax calculation
 * (see MergeFeeDetailsPlugin). Strict no-op when no item has a fee.
 */
class MapFeeItemsPlugin
{
    /**
     * @param QuoteDetailsItemInterfaceFactory $itemFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly QuoteDetailsItemInterfaceFactory $itemFactory,
        private readonly TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * @param CommonTaxCollector $subject
     * @param QuoteDetailsItemInterface[] $result
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @return QuoteDetailsItemInterface[]
     */
    public function afterMapItems(
        CommonTaxCollector $subject,
        $result,
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ) {
        if (empty($result)) {
            return $result;
        }
        $feeByCode = [];
        foreach ($shippingAssignment->getItems() as $item) {
            $fee = (float)$item->getData($useBaseCurrency ? FeeKeys::BASE_CONFIGURED : FeeKeys::CONFIGURED);
            if ($fee > 0.0 && $item->getTaxCalculationItemId()) {
                $feeByCode[$item->getTaxCalculationItemId()] = $fee;
            }
        }
        if (!$feeByCode) {
            return $result;
        }

        $mapped = [];
        foreach ($result as $detailsItem) {
            $code = $detailsItem->getCode();
            if ($detailsItem->getType() !== CommonTaxCollector::ITEM_TYPE_PRODUCT
                || $detailsItem->getParentCode() !== null
                || !isset($feeByCode[$code])
            ) {
                $mapped[] = $detailsItem;
                continue;
            }
            $mapped[] = $detailsItem;
            $mapped[] = $this->createFeeItem($detailsItem, $feeByCode[$code]);
        }
        return $mapped;
    }

    /**
     * Build the companion row and split the item's discount between product and fee rows.
     *
     * @param QuoteDetailsItemInterface $product
     * @param float $fee
     * @return QuoteDetailsItemInterface
     */
    private function createFeeItem(QuoteDetailsItemInterface $product, float $fee): QuoteDetailsItemInterface
    {
        $productKey = $product->getTaxClassKey();
        $taxClassKey = $this->taxClassKeyFactory->create()
            ->setType($productKey->getType())
            ->setValue($productKey->getValue());

        $discount = (float)$product->getDiscountAmount();
        $productRow = $this->priceCurrency->round($product->getUnitPrice()) * $product->getQuantity();
        $feeDiscount = 0.0;
        if ($discount > 0.0 && ($productRow + $fee) > 0.0) {
            $feeDiscount = min($fee, (float)$this->priceCurrency->round($discount * $fee / ($productRow + $fee)));
            $product->setDiscountAmount($discount - $feeDiscount);
        }

        return $this->itemFactory->create()
            ->setCode(FeeKeys::TAX_ITEM_CODE_PREFIX . $product->getCode())
            ->setType(FeeKeys::TAX_ITEM_TYPE)
            ->setQuantity(1)
            ->setUnitPrice($fee)
            ->setTaxClassKey($taxClassKey)
            ->setIsTaxIncluded($product->getIsTaxIncluded())
            ->setDiscountAmount($feeDiscount)
            ->setAssociatedItemCode($product->getCode())
            ->setParentCode(null);
    }
}
