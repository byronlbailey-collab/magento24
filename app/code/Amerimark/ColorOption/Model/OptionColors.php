<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model;

use Amerimark\ColorOption\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\App\ResourceConnection;

/**
 * Database access for the colors chosen on product options: the value -> color mapping and the color catalogue
 * the product form offers.
 */
class OptionColors
{
    public const OPTION_TYPE = 'color_swatch';

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * Colors by id: item_id => [item_id, group_id, name, hex, pms, comment, is_active].
     *
     * @param int[] $colorIds
     * @return array<int, array>
     */
    public function getColors(array $colorIds): array
    {
        $colorIds = array_values(array_filter(array_map('intval', $colorIds)));
        if (!$colorIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName(GroupResource::ITEM_TABLE))
                ->where('item_id IN (?)', $colorIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['item_id']] = $row;
        }
        return $result;
    }

    /**
     * Color id of each option value: option_type_id => color_id.
     *
     * @param int[] $valueIds
     * @return array<int, int>
     */
    public function getColorIdsByValueIds(array $valueIds): array
    {
        $valueIds = array_values(array_filter(array_map('intval', $valueIds)));
        if (!$valueIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $pairs = $connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName(GroupResource::VALUE_MAP_TABLE), ['option_type_id', 'color_id'])
                ->where('option_type_id IN (?)', $valueIds)
        );
        return array_map('intval', $pairs);
    }

    /**
     * What the storefront needs to draw each value of a color option: option_type_id => [name, hex, is_active].
     *
     * @param int[] $valueIds
     * @return array<int, array{name: string, hex: string, is_active: bool}>
     */
    public function getSwatchData(array $valueIds): array
    {
        $valueIds = array_values(array_filter(array_map('intval', $valueIds)));
        if (!$valueIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['m' => $this->resource->getTableName(GroupResource::VALUE_MAP_TABLE)], ['option_type_id'])
                ->join(
                    ['i' => $this->resource->getTableName(GroupResource::ITEM_TABLE)],
                    'i.item_id = m.color_id',
                    ['name', 'hex', 'is_active']
                )
                ->where('m.option_type_id IN (?)', $valueIds)
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['option_type_id']] = [
                'name' => (string)$row['name'],
                'hex' => (string)$row['hex'],
                'is_active' => (bool)$row['is_active'],
            ];
        }
        return $result;
    }

    /**
     * Ids of the values (option_type_id) a product option currently has.
     *
     * @param int $optionId
     * @return int[]
     */
    public function getValueIdsOfOption(int $optionId): array
    {
        $connection = $this->resource->getConnection();
        return array_map(
            'intval',
            $connection->fetchCol(
                $connection->select()
                    ->from($this->resource->getTableName(GroupResource::TYPE_VALUE_TABLE), ['option_type_id'])
                    ->where('option_id = ?', $optionId)
            )
        );
    }

    /**
     * Remember which color an option value shows (and make its default title the color's name).
     *
     * @param int $valueId
     * @param int $colorId
     * @return void
     */
    public function saveMapping(int $valueId, int $colorId): void
    {
        $connection = $this->resource->getConnection();
        $connection->insertOnDuplicate(
            $this->resource->getTableName(GroupResource::VALUE_MAP_TABLE),
            ['option_type_id' => $valueId, 'color_id' => $colorId],
            ['color_id']
        );
        $this->syncTitle($valueId, $colorId);
    }

    /**
     * The shopper-facing title of a color value is always the color's name: write it as the default title and drop
     * per-store overrides.
     *
     * @param int $valueId
     * @param int $colorId
     * @return void
     */
    private function syncTitle(int $valueId, int $colorId): void
    {
        $connection = $this->resource->getConnection();
        $titles = $this->resource->getTableName(GroupResource::TYPE_TITLE_TABLE);
        $name = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName(GroupResource::ITEM_TABLE), ['name'])
                ->where('item_id = ?', $colorId)
        );
        if ($name === false) {
            return;
        }
        $connection->insertOnDuplicate(
            $titles,
            ['option_type_id' => $valueId, 'store_id' => 0, 'title' => $name],
            ['title']
        );
        $connection->delete($titles, ['option_type_id = ?' => $valueId, 'store_id <> ?' => 0]);
    }

    /**
     * Forget the color mapping of every value of an option (the option stopped being a Color option).
     *
     * @param int $optionId
     * @return void
     */
    public function deleteMappingsOfOption(int $optionId): void
    {
        $connection = $this->resource->getConnection();
        $valueIds = $this->getValueIdsOfOption($optionId);
        if ($valueIds) {
            $connection->delete(
                $this->resource->getTableName(GroupResource::VALUE_MAP_TABLE),
                ['option_type_id IN (?)' => $valueIds]
            );
        }
    }

    /**
     * What the product form offers: every group, each group's colors with their hex value, and the staff comments.
     *
     * @return array{groups: array[], colors: array<int, array[]>, comments: array<int, string>}
     */
    public function getCatalog(): array
    {
        $connection = $this->resource->getConnection();
        $groups = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName(GroupResource::TABLE), ['group_id', 'name', 'is_active'])
                ->order('name ASC')
        );
        $items = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resource->getTableName(GroupResource::ITEM_TABLE),
                    ['item_id', 'group_id', 'name', 'hex', 'is_active', 'comment']
                )
                ->order(['sort_order ASC', 'item_id ASC'])
        );
        $colors = [];
        $comments = [];
        foreach ($items as $item) {
            if (trim((string)$item['comment']) !== '') {
                $comments[(int)$item['item_id']] = trim((string)$item['comment']);
            }
            $colors[(int)$item['group_id']][] = [
                'value' => (int)$item['item_id'],
                'label' => $item['name'] . ($item['is_active'] ? '' : ' (' . __('disabled') . ')'),
                'hex' => $item['hex'],
            ];
        }
        return ['groups' => $groups, 'colors' => $colors, 'comments' => $comments];
    }
}
