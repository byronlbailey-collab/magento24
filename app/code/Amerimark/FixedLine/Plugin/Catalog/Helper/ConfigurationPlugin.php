<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Helper;

use Amerimark\FixedLine\Model\FeeCalculator;
use Magento\Catalog\Helper\Product\Configuration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Appends "(+$50.00 per line)" to the cart/minicart/checkout/wishlist labels of FixedLine options.
 */
class ConfigurationPlugin
{
    /**
     * @param FeeCalculator $feeCalculator
     * @param PriceCurrencyInterface $priceCurrency
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly FeeCalculator $feeCalculator,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @param Configuration $subject
     * @param array $result
     * @param ItemInterface $item
     * @return array
     */
    public function afterGetCustomOptions(Configuration $subject, $result, ItemInterface $item)
    {
        $product = $item->getProduct();
        foreach ($result as &$row) {
            if (!isset($row['option_id'])) {
                continue;
            }
            $option = $product->getOptionById($row['option_id']);
            $selection = $item->getOptionByCode('option_' . $row['option_id']);
            if (!$option || !$selection) {
                continue;
            }
            $fee = $this->feeCalculator->getOptionFee($option, (string)$selection->getValue());
            if ($fee <= 0.0) {
                continue;
            }
            $suffix = ' ' . $this->escaper->escapeHtml(
                (string)__('(+%1 per line)', $this->priceCurrency->convertAndFormat($fee, false))
            );
            foreach (['value', 'print_value'] as $key) {
                if (isset($row[$key]) && is_string($row[$key])) {
                    $row[$key] .= $suffix;
                }
            }
        }
        return $result;
    }
}
