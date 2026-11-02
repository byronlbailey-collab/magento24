<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model;

use Amerimark\ImageOption\Model\ResourceModel\Group as GroupResource;
use Magento\Catalog\Model\Product\Option;

/**
 * Storefront lookups of the images an Image Group option offers.
 *
 * An image is selectable only while it is active AND its group is active; items come back with display URLs.
 */
class GroupItems
{
    /**
     * @var array<int, array[]>
     */
    private array $cache = [];

    /**
     * @param GroupFactory $groupFactory
     * @param GroupResource $groupResource
     * @param ImageUrl $imageUrl
     * @param ImageResizer $imageResizer
     */
    public function __construct(
        private readonly GroupFactory $groupFactory,
        private readonly GroupResource $groupResource,
        private readonly ImageUrl $imageUrl,
        private readonly ImageResizer $imageResizer
    ) {
    }

    /**
     * Selectable images for an option, in display order (empty when the group is missing or inactive).
     *
     * @param Option $option
     * @return array[] each: item_id, label, sku, image, url, thumb
     */
    public function getItemsForOption(Option $option): array
    {
        $groupId = (int)$option->getData('image_group_id');
        if ($groupId <= 0) {
            return [];
        }
        if (!isset($this->cache[$groupId])) {
            $this->cache[$groupId] = $this->load($groupId);
        }
        return $this->cache[$groupId];
    }

    /**
     * A selectable image of the option's group, or null when it is unknown, inactive or in another group.
     *
     * @param Option $option
     * @param int $itemId
     * @return array|null
     */
    public function findActiveItem(Option $option, int $itemId): ?array
    {
        foreach ($this->getItemsForOption($option) as $item) {
            if ((int)$item['item_id'] === $itemId) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Any image of the option's group regardless of active state (for showing what was chosen).
     *
     * @param Option $option
     * @param int $itemId
     * @return array|null
     */
    public function findItem(Option $option, int $itemId): ?array
    {
        $groupId = (int)$option->getData('image_group_id');
        if ($groupId <= 0) {
            return null;
        }
        foreach ($this->groupResource->getItemsData($groupId) as $row) {
            if ((int)$row['item_id'] === $itemId) {
                return $this->format($row);
            }
        }
        return null;
    }

    /**
     * @param int $groupId
     * @return array[]
     */
    private function load(int $groupId): array
    {
        $group = $this->groupFactory->create()->load($groupId);
        if (!$group->getId() || !$group->getIsActive()) {
            return [];
        }
        return array_map([$this, 'format'], $this->groupResource->getItemsData($groupId, true));
    }

    /**
     * @param array $row
     * @return array
     */
    private function format(array $row): array
    {
        return [
            'item_id' => (int)$row['item_id'],
            'label' => (string)$row['label'],
            'sku' => (string)($row['sku'] ?? ''),
            'image' => (string)$row['image'],
            'url' => $this->imageUrl->getUrl((string)$row['image']),
            'thumb' => $this->imageResizer->getThumbnailUrl((string)$row['image']),
        ];
    }
}
