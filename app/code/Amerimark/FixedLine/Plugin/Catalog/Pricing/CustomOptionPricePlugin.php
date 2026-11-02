<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Pricing;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Pricing\Price\CustomOptionPrice;

/**
 * Zeroes the min/max per-unit range of non-select FixedLine options (select values are handled by the
 * CustomOptionPriceCalculator plugin).
 */
class CustomOptionPricePlugin
{
    /**
     * @param CustomOptionPrice $subject
     * @param array $result
     * @return array
     */
    public function afterGetValue(CustomOptionPrice $subject, $result)
    {
        if (!is_array($result) || !$result) {
            return $result;
        }
        $product = $subject->getProduct();
        foreach ($result as &$row) {
            $option = $product->getOptionById($row['option_id'] ?? 0);
            if ($option && $option->getGroupByType() !== Option::OPTION_GROUP_SELECT
                && PriceType::isFixedLine($option->getPriceType())
            ) {
                $row['min'] = 0.0;
                $row['max'] = 0.0;
            }
        }
        return $result;
    }
}
