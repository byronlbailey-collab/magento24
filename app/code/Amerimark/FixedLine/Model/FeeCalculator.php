<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Model;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Computes the FixedLine fee for one cart line from the line's own selected options.
 */
class FeeCalculator
{
    /**
     * When true, an admin/API custom price waives the fee (core: a custom price replaces option prices).
     */
    public const WAIVE_FEE_ON_CUSTOM_PRICE = true;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(private readonly PriceCurrencyInterface $priceCurrency)
    {
    }

    /**
     * Get the per-line fee in base and quote currency (zeros when the line carries no fee).
     *
     * @param AbstractItem $item
     * @return array{base: float, quote: float}
     */
    public function getFees(AbstractItem $item): array
    {
        $none = ['base' => 0.0, 'quote' => 0.0];
        if ($item->getParentItem()
            || ($item->getHasChildren() && $item->isChildrenCalculated())
            || $item->isDeleted()
            || $item->getTotalQty() <= 0
        ) {
            return $none;
        }
        if (self::WAIVE_FEE_ON_CUSTOM_PRICE
            && ($item->getCustomPrice() !== null || $item->getOriginalCustomPrice() !== null)
        ) {
            return $none;
        }
        if ($item instanceof AddressItem && !$this->isFeeOwner($item)) {
            return $none;
        }

        $sum = $this->sumFixedLineOptions($item);
        if ($sum <= 0.0) {
            return $none;
        }
        $base = (float)$this->priceCurrency->round($sum);
        $quote = (float)$this->priceCurrency->round($this->priceCurrency->convert($base, $item->getStore()));
        return ['base' => $base, 'quote' => $quote];
    }

    /**
     * Sum raw fxline prices of the selected options (base currency, unrounded).
     *
     * Never uses getPrice(true)/percent logic, so tier, special and catalog-rule prices cannot touch the fee.
     *
     * @param AbstractItem $item
     * @return float
     */
    private function sumFixedLineOptions(AbstractItem $item): float
    {
        $idsOption = $item->getOptionByCode('option_ids');
        $product = $item->getProduct();
        if (!$idsOption || !$product || (string)$idsOption->getValue() === '') {
            return 0.0;
        }
        $sum = 0.0;
        foreach (explode(',', (string)$idsOption->getValue()) as $optionId) {
            $option = $product->getOptionById($optionId);
            $selection = $item->getOptionByCode('option_' . $optionId);
            if (!$option || !$selection) {
                continue;
            }
            $sum += $this->getOptionFee($option, (string)$selection->getValue());
        }
        return $sum;
    }

    /**
     * Raw FixedLine fee (base currency, unrounded) of one selected option.
     *
     * @param Option $option
     * @param string $selection the cart selection: text/date value, or comma separated option type ids
     * @return float
     */
    public function getOptionFee(Option $option, string $selection): float
    {
        if ($option->getGroupByType() !== Option::OPTION_GROUP_SELECT) {
            return PriceType::isFixedLine($option->getPriceType()) ? (float)$option->getData('price') : 0.0;
        }
        $fee = 0.0;
        foreach (explode(',', $selection) as $valueId) {
            $value = $option->getValueById($valueId);
            if ($value && PriceType::isFixedLine($value->getPriceType())) {
                $fee += (float)$value->getData('price');
            }
        }
        return $fee;
    }

    /**
     * Multishipping: one cart line pays the fee once, on the first address item that references it.
     *
     * @param AddressItem $item
     * @return bool
     */
    private function isFeeOwner(AddressItem $item): bool
    {
        $quote = $item->getQuote();
        if (!$quote) {
            return true;
        }
        foreach ($quote->getAllShippingAddresses() as $address) {
            foreach ($address->getAllItems() as $candidate) {
                if ($candidate instanceof AddressItem
                    && (int)$candidate->getQuoteItemId() === (int)$item->getQuoteItemId()
                ) {
                    return $candidate === $item;
                }
            }
        }
        return true;
    }
}
