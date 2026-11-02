<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\GraphQl;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Wishlist\Model\Item\Option as SelectedOption;
use Magento\WishlistGraphQl\Model\WishlistItem\DataProvider\CustomizableOptionValue\PriceUnitLabel;
use Magento\WishlistGraphQl\Model\WishlistItem\DataProvider\CustomizableOptionValueInterface;

/**
 * Selected value of an Image Group option on a wishlist item: the image label (or uploaded file name); no price.
 */
class WishlistImageGroupValue implements CustomizableOptionValueInterface
{
    private const OPTION_TYPE = 'custom-option';

    /**
     * @param PriceUnitLabel $priceUnitLabel
     * @param Uid $uidEncoder
     */
    public function __construct(
        private readonly PriceUnitLabel $priceUnitLabel,
        private readonly Uid $uidEncoder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getData(WishlistItem $wishlistItem, Option $option, SelectedOption $selectedOption): array
    {
        $type = $option->groupFactory($option->getType());
        $type->setOption($option);
        return [[
            'id' => $selectedOption->getId(),
            'customizable_option_value_uid' => $this->uidEncoder->encode(self::OPTION_TYPE . '/' . $option->getOptionId()),
            'label' => '',
            'value' => $type->getPrintableOptionValue($selectedOption->getValue()),
            'price' => [
                'type' => 'FIXED',
                'units' => $this->priceUnitLabel->getData('fixed'),
                'value' => 0.0,
            ],
        ]];
    }
}
