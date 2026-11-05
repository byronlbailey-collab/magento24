<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\CatalogGraphQl;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\CatalogGraphQl\Model\Resolver\Product\Options;
use Magento\Framework\GraphQl\Query\Uid;

/**
 * Adds the images (and upload settings) of Image Group options to the product's customizable options.
 */
class OptionsResolverPlugin
{
    private const OPTION_TYPE = 'custom-option';

    /**
     * @param GroupItems $groupItems
     * @param Uid $uidEncoder
     */
    public function __construct(
        private readonly GroupItems $groupItems,
        private readonly Uid $uidEncoder
    ) {
    }

    /**
     * @param Options $subject
     * @param array|null $result
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param mixed $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @return array|null
     */
    public function afterResolve(Options $subject, $result, $field, $context, $info, ?array $value = null)
    {
        if (!is_array($result) || !isset($value['model'])) {
            return $result;
        }
        $product = $value['model'];
        foreach ($result as $key => $entry) {
            if (($entry['type'] ?? null) !== ImageGroup::OPTION_TYPE) {
                continue;
            }
            $option = $product->getOptionById($entry['option_id'] ?? 0);
            if (!$option) {
                continue;
            }
            $images = [];
            foreach ($this->groupItems->getItemsForOption($option) as $item) {
                $images[] = [
                    'uid' => $this->uidEncoder->encode(self::OPTION_TYPE . '/' . $option->getId() . '/' . $item['item_id']),
                    'label' => $item['label'],
                    'sku' => $item['sku'] !== '' ? $item['sku'] : null,
                    'image_url' => $item['url'],
                    'thumbnail_url' => $item['thumb'],
                ];
            }
            $result[$key]['value']['images'] = $images;
            $result[$key]['value']['allow_upload'] = (bool)$option->getData('image_allow_upload');
            $result[$key]['value']['upload_file_extensions'] = $option->getData('image_allow_upload')
                ? (trim((string)$option->getData('image_upload_ext')) ?: ImageGroup::DEFAULT_UPLOAD_EXTENSIONS)
                : null;
        }
        return $result;
    }
}
