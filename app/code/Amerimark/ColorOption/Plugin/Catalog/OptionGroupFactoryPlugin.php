<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Catalog;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Model\Product\Option\Type\ColorSwatchFactory;
use Magento\Catalog\Model\Product\Option;

/**
 * Color options belong to the core "select" group (that is what makes them price, save and reorder like a
 * drop-down), but are handled by their own option type class.
 */
class OptionGroupFactoryPlugin
{
    /**
     * @param ColorSwatchFactory $factory
     */
    public function __construct(private readonly ColorSwatchFactory $factory)
    {
    }

    /**
     * @param Option $subject
     * @param callable $proceed
     * @param string|null $type
     * @return \Magento\Catalog\Model\Product\Option\Type\DefaultType
     */
    public function aroundGroupFactory(Option $subject, callable $proceed, $type)
    {
        return $type === OptionColors::OPTION_TYPE ? $this->factory->create() : $proceed($type);
    }
}
