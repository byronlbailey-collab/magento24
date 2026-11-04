<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\GraphQl;

use Magento\Catalog\Model\Product\Option;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as SelectedOption;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue\Dropdown;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValueInterface;

/**
 * Selected value of a Color option on a cart line: the drop-down data, with the plain color name as the label (the
 * option type's formatted value is swatch HTML).
 */
class CartColorValue implements CustomizableOptionValueInterface
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
    public function getData(QuoteItem $cartItem, Option $option, SelectedOption $selectedOption): array
    {
        $type = $option->groupFactory($option->getType())->setOption($option);
        $name = $type->getPrintableOptionValue($selectedOption->getValue());
        $data = $this->dropdown->getData($cartItem, $option, $selectedOption);
        foreach ($data as $i => $row) {
            $data[$i]['label'] = $name;
        }
        return $data;
    }
}
