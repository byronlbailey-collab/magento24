<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\GraphQl;

use Magento\Catalog\Model\Product\Option;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Wishlist\Model\Item\Option as SelectedOption;
use Magento\WishlistGraphQl\Model\WishlistItem\DataProvider\CustomizableOptionValue\Dropdown;
use Magento\WishlistGraphQl\Model\WishlistItem\DataProvider\CustomizableOptionValueInterface;

/**
 * Selected value of a Color option on a wishlist item: the drop-down data, with the plain color name as the label.
 */
class WishlistColorValue implements CustomizableOptionValueInterface
{
    /**
     * @param Dropdown $dropdown
     */
    public function __construct(private readonly Dropdown $dropdown)
    {
    }

    /**
     * @inheritdoc
     */
    public function getData(WishlistItem $wishlistItem, Option $option, SelectedOption $selectedOption): array
    {
        $type = $option->groupFactory($option->getType())->setOption($option);
        $name = $type->getPrintableOptionValue($selectedOption->getValue());
        $data = $this->dropdown->getData($wishlistItem, $option, $selectedOption);
        foreach ($data as $i => $row) {
            $data[$i]['label'] = $name;
        }
        return $data;
    }
}
