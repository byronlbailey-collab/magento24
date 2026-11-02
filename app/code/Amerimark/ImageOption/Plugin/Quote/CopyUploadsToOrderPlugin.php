<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Quote;

use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;

/**
 * Copies files customers uploaded for an Image Group option from the quote path to the order path when the order is
 * placed.
 *
 * Core does this in a plugin that only looks at options of type "file", so without this an order would point at an
 * order_path that was never written, and the file would vanish when old quotes are cleaned up.
 */
class CopyUploadsToOrderPlugin
{
    /**
     * @param ToOrderItem $subject
     * @param AbstractItem $item
     * @param array $data
     * @return void
     */
    public function beforeConvert(ToOrderItem $subject, AbstractItem $item, $data = []): void
    {
        if (!is_array($item->getOptions())) {
            return;
        }
        foreach ($item->getOptions() as $itemOption) {
            $code = $itemOption->getCode() === null ? [] : explode('_', $itemOption->getCode());
            if (!isset($code[1]) || !is_numeric($code[1])) {
                continue;
            }
            $option = $item->getProduct()->getOptionById($code[1]);
            if (!$option || $option->getType() !== ImageGroup::OPTION_TYPE) {
                continue;
            }
            try {
                $option->groupFactory($option->getType())
                    ->setQuoteItemOption($itemOption)
                    ->copyQuoteToOrder();
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
