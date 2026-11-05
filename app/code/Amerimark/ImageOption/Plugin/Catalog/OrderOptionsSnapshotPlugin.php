<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Catalog;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;

/**
 * Adds an order-time snapshot of the chosen image to the order item's option data.
 *
 * Order items keep their options as stored text, so later edits to the group never rewrite history: the displayed
 * value/print_value are already snapshots, and this adds the label, SKU, image file and group the image had when the
 * order was placed. Uploads need nothing extra (the core file copy keeps the file).
 */
class OrderOptionsSnapshotPlugin
{
    public const SNAPSHOT_KEY = 'amerimark_image';

    /**
     * @param GroupItems $groupItems
     */
    public function __construct(private readonly GroupItems $groupItems)
    {
    }

    /**
     * @param AbstractType $subject
     * @param array $result
     * @param Product $product
     * @return array
     */
    public function afterGetOrderOptions(AbstractType $subject, $result, $product)
    {
        foreach ($result['options'] ?? [] as $i => $entry) {
            if (($entry['option_type'] ?? null) !== ImageGroup::OPTION_TYPE
                || !ImageGroup::isImageValue($entry['option_value'] ?? null)
            ) {
                continue;
            }
            $option = $product->getOptionById($entry['option_id'] ?? 0);
            $item = $option ? $this->groupItems->findItem($option, (int)$entry['option_value']) : null;
            if ($item) {
                $result['options'][$i][self::SNAPSHOT_KEY] = [
                    'item_id' => $item['item_id'],
                    'group_id' => (int)$option->getData('image_group_id'),
                    'label' => $item['label'],
                    'sku' => $item['sku'],
                    'image' => $item['image'],
                ];
            }
        }
        return $result;
    }
}
