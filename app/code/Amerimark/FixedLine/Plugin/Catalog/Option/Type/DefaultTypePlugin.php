<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Option\Type;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;

/**
 * Makes FixedLine options contribute 0 to the per-unit price.
 *
 * Declared on DefaultType; inherited by the Select/Text/File/Date interceptors.
 */
class DefaultTypePlugin
{
    /**
     * @param DefaultType $subject
     * @param callable $proceed
     * @param mixed $optionValue
     * @param float $basePrice
     * @return float
     */
    public function aroundGetOptionPrice(DefaultType $subject, callable $proceed, $optionValue, $basePrice)
    {
        $option = $subject->getOption();
        if (!$option) {
            return $proceed($optionValue, $basePrice);
        }
        if ($option->getGroupByType() !== Option::OPTION_GROUP_SELECT) {
            return PriceType::isFixedLine($option->getPriceType()) ? 0.0 : $proceed($optionValue, $basePrice);
        }

        $ids = explode(',', (string)$optionValue);
        $rest = [];
        $dropped = false;
        foreach ($ids as $id) {
            $value = $option->getValueById($id);
            if ($value && PriceType::isFixedLine($value->getPriceType())) {
                $dropped = true;
                continue;
            }
            $rest[] = $id;
        }
        if (!$dropped) {
            return $proceed($optionValue, $basePrice);
        }
        return $rest ? $proceed(implode(',', $rest), $basePrice) : 0.0;
    }
}
