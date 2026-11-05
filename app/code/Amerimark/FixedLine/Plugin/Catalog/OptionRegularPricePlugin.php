<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Model\Product\Option;

/**
 * Non-select FixedLine options have no per-unit regular price (GraphQL original_item_price, price JS config).
 */
class OptionRegularPricePlugin
{
    /**
     * @param Option $subject
     * @param callable $proceed
     * @return float|int|null
     */
    public function aroundGetRegularPrice(Option $subject, callable $proceed)
    {
        return PriceType::isFixedLine($subject->getPriceType()) ? 0.0 : $proceed();
    }
}
