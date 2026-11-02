<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\GraphQl;

use Magento\Catalog\Model\Product\Option;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as SelectedOption;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue\PriceUnitLabel;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValueInterface;

/**
 * Selected value of an Image Group option on a cart line: the image label (or uploaded file name); no price.
 */
class CartImageGroupValue implements CustomizableOptionValueInterface
{
    /**
     * @param PriceUnitLabel $priceUnitLabel
     */
    public function __construct(private readonly PriceUnitLabel $priceUnitLabel)
    {
    }

    /**
     * @inheritdoc
     */
    public function getData(QuoteItem $cartItem, Option $option, SelectedOption $selectedOption): array
    {
        $type = $option->groupFactory($option->getType());
        $type->setOption($option);
        return [[
            'id' => $selectedOption->getId(),
            'label' => $option->getTitle(),
            'value' => $type->getPrintableOptionValue($selectedOption->getValue()),
            'price' => [
                'type' => 'FIXED',
                'units' => $this->priceUnitLabel->getData('fixed'),
                'value' => 0.0,
            ],
        ]];
    }
}
