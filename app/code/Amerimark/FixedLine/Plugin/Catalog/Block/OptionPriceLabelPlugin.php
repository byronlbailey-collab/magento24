<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Block;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Block\Product\View\Options\AbstractOptions;

/**
 * Adds " per line" to the server-rendered price notice of FixedLine options and checkable (radio/checkbox) values.
 */
class OptionPriceLabelPlugin
{
    /**
     * Text/file/date options.
     *
     * @param AbstractOptions $subject
     * @param string $result
     * @return string
     */
    public function afterGetFormattedPrice(AbstractOptions $subject, $result)
    {
        $option = $subject->getOption();
        if ($result !== '' && $option && PriceType::isFixedLine($option->getPriceType())) {
            return $result . ' ' . __('per line');
        }
        return $result;
    }

    /**
     * Radio/checkbox values.
     *
     * @param AbstractOptions $subject
     * @param string $result
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface $value
     * @return string
     */
    public function afterFormatPrice(AbstractOptions $subject, $result, $value)
    {
        if ($result !== '' && PriceType::isFixedLine($value->getPriceType())) {
            return $result . ' ' . __('per line');
        }
        return $result;
    }
}
