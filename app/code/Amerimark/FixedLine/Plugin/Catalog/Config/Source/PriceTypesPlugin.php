<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Config\Source;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Model\Config\Source\Product\Options\Price;

/**
 * Registers the FixedLine price type with the customizable option price source.
 *
 * The admin form, option validators and legacy option block all read this source.
 */
class PriceTypesPlugin
{
    /**
     * Add the FixedLine type to the price type dropdown (and validator whitelist).
     *
     * @param Price $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray(Price $subject, array $result): array
    {
        $result[] = ['value' => PriceType::CODE, 'label' => __('Fixed per Line')];
        return $result;
    }

    /**
     * Add the currency prefix shown next to the price input for FixedLine.
     *
     * @param Price $subject
     * @param array $result
     * @return array
     */
    public function afterPrefixesToOptionArray(Price $subject, array $result): array
    {
        foreach ($result as $item) {
            if ($item['value'] === Price::VALUE_FIXED) {
                $result[] = ['value' => PriceType::CODE, 'label' => $item['label']];
                break;
            }
        }
        return $result;
    }
}
