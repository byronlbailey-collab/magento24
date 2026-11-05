<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Pricing;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Pricing\Price\CustomOptionPriceCalculator;

/**
 * FixedLine values contribute 0 to every per-unit option price (product page JS config, From/To ranges).
 */
class CustomOptionPriceCalculatorPlugin
{
    /**
     * @param CustomOptionPriceCalculator $subject
     * @param callable $proceed
     * @param Value $optionValue
     * @param string $priceCode
     * @return float|int
     */
    public function aroundGetOptionPriceByPriceCode(
        CustomOptionPriceCalculator $subject,
        callable $proceed,
        Value $optionValue,
        string $priceCode = 'base_price'
    ) {
        if (PriceType::isFixedLine($optionValue->getPriceType())) {
            return 0.0;
        }
        return $proceed($optionValue, $priceCode);
    }
}
