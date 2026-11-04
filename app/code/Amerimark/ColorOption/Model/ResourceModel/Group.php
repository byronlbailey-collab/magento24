<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\ResourceModel;

use Amerimark\ColorOption\Model\HexColor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Group extends AbstractDb
{
    public const TABLE = 'amerimark_color_group';
    public const ITEM_TABLE = 'amerimark_color_group_item';
    public const VALUE_MAP_TABLE = 'amerimark_color_option_value';
    public const TYPE_VALUE_TABLE = 'catalog_product_option_type_value';
    public const TYPE_TITLE_TABLE = 'catalog_product_option_type_title';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, 'group_id');
    }

    /**
     * Number of product options (across all products) that use at least one color of the group.
     *
     * @param int $groupId
     * @return int
     */
    public function countUsages(int $groupId): int
    {
        $connection = $this->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from(['m' => $this->getTable(self::VALUE_MAP_TABLE)], [])
                ->join(['i' => $this->getTable(self::ITEM_TABLE)], 'i.item_id = m.color_id', [])
                ->join(['v' => $this->getTable(self::TYPE_VALUE_TABLE)], 'v.option_type_id = m.option_type_id', [])
                ->columns(['COUNT(DISTINCT v.option_id)'])
                ->where('i.group_id = ?', $groupId)
        );
    }

    /**
     * Colors of a group as plain arrays, ordered for display.
     *
     * @param int $groupId
     * @param bool $activeOnly
     * @return array[]
     */
    public function getItemsData(int $groupId, bool $activeOnly = false): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::ITEM_TABLE))
            ->where('group_id = ?', $groupId)
            ->order(['sort_order ASC', 'item_id ASC']);
        if ($activeOnly) {
            $select->where('is_active = 1');
        }
        return $connection->fetchAll($select);
    }

    /**
     * How many product option values use each color of a group.
     *
     * @param int $groupId
     * @return array<int, int> color id => usage count
     */
    public function getColorUsages(int $groupId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['m' => $this->getTable(self::VALUE_MAP_TABLE)], ['color_id', 'cnt' => 'COUNT(*)'])
            ->join(['i' => $this->getTable(self::ITEM_TABLE)], 'i.item_id = m.color_id', [])
            ->where('i.group_id = ?', $groupId)
            ->group('m.color_id');
        return array_map('intval', $connection->fetchPairs($select));
    }

    /**
     * Refuse to delete a group that products still use (the DB foreign key would reject it too).
     *
     * @param AbstractModel $object
     * @return $this
     * @throws LocalizedException
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $usages = $this->countUsages((int)$object->getId());
        if ($usages > 0) {
            throw new LocalizedException(
                __('This group is used by %1 product option(s) and cannot be deleted. Remove it from those options first.', $usages)
            );
        }
        return parent::_beforeDelete($object);
    }

    /**
     * Save the group's colors. Colors may share a name inside a group. A color that product options use cannot be
     * deleted (disable it instead), and a rename must not give any product option two colors of the same name.
     *
     * @param AbstractModel $object
     * @return $this
     * @throws LocalizedException
     */
    protected function _afterSave(AbstractModel $object)
    {
        $items = $object->getData('items');
        if (is_array($items)) {
            $this->saveItems((int)$object->getId(), $items);
        }
        return parent::_afterSave($object);
    }

    /**
     * @param int $groupId
     * @param array[] $items rows with optional item_id, and name, hex, pms, comment, sort_order, is_active
     * @return void
     * @throws LocalizedException
     */
    private function saveItems(int $groupId, array $items): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable(self::ITEM_TABLE);
        $existing = $connection->fetchPairs(
            $connection->select()->from($table, ['item_id', 'name'])->where('group_id = ?', $groupId)
        );

        $rows = [];
        $keep = [];
        $renamed = [];
        foreach ($items as $row) {
            $name = trim((string)($row['name'] ?? ''));
            $hex = HexColor::normalize($row['hex'] ?? '');
            if ($name === '' || $hex === null) {
                throw new LocalizedException(__('Every color needs a name and a valid display color (#RRGGBB).'));
            }
            $itemId = (int)($row['item_id'] ?? 0);
            $isExisting = $itemId > 0 && isset($existing[$itemId]);
            if ($isExisting) {
                $keep[] = $itemId;
                if ($existing[$itemId] !== $name) {
                    $renamed[$itemId] = $name;
                }
            }
            $pms = trim((string)($row['pms'] ?? ''));
            $comment = trim((string)($row['comment'] ?? ''));
            $rows[] = [
                'item_id' => $isExisting ? $itemId : 0,
                'data' => [
                    'group_id' => $groupId,
                    'name' => $name,
                    'hex' => $hex,
                    'pms' => $pms !== '' ? mb_substr($pms, 0, 64) : null,
                    'comment' => $comment !== '' ? $comment : null,
                    'sort_order' => max(0, (int)($row['sort_order'] ?? 0)),
                    'is_active' => empty($row['is_active']) ? 0 : 1,
                ],
            ];
        }

        $remove = array_diff(array_map('intval', array_keys($existing)), $keep);
        $this->assertRemovedColorsUnused($remove, $existing);
        if ($renamed) {
            $this->assertRenamesKeepOptionNamesUnique($renamed, $existing);
        }

        foreach ($rows as $row) {
            if ($row['item_id']) {
                $connection->update($table, $row['data'], ['item_id = ?' => $row['item_id']]);
            } else {
                $connection->insert($table, $row['data']);
            }
        }
        if ($remove) {
            $connection->delete($table, ['item_id IN (?)' => $remove]);
        }
        foreach ($renamed as $itemId => $newName) {
            $this->syncOptionValueTitles((int)$itemId, $newName);
        }
    }

    /**
     * @param int[] $removeIds
     * @param array<int, string> $existing
     * @return void
     * @throws LocalizedException
     */
    private function assertRemovedColorsUnused(array $removeIds, array $existing): void
    {
        if (!$removeIds) {
            return;
        }
        $connection = $this->getConnection();
        $used = $connection->fetchPairs(
            $connection->select()
                ->from($this->getTable(self::VALUE_MAP_TABLE), ['color_id', 'cnt' => 'COUNT(*)'])
                ->where('color_id IN (?)', $removeIds)
                ->group('color_id')
        );
        if ($used) {
            $names = array_map(fn ($id) => '"' . $existing[(int)$id] . '"', array_keys($used));
            throw new LocalizedException(
                __('Cannot delete %1: product options still use it. Disable the color instead, or remove it from those options first.', implode(', ', $names))
            );
        }
    }

    /**
     * A product option may hold only one color of a given name, so a rename must not create a clash.
     *
     * @param array<int, string> $renamed color id => new name
     * @param array<int, string> $existing color id => current name (this group)
     * @return void
     * @throws LocalizedException
     */
    private function assertRenamesKeepOptionNamesUnique(array $renamed, array $existing): void
    {
        $connection = $this->getConnection();
        $mapTable = $this->getTable(self::VALUE_MAP_TABLE);
        $valueTable = $this->getTable(self::TYPE_VALUE_TABLE);
        $affected = $connection->fetchCol(
            $connection->select()->distinct()
                ->from(['m' => $mapTable], [])
                ->join(['v' => $valueTable], 'v.option_type_id = m.option_type_id', ['option_id'])
                ->where('m.color_id IN (?)', array_keys($renamed))
        );
        if (!$affected) {
            return;
        }
        $members = $connection->fetchAll(
            $connection->select()
                ->from(['m' => $mapTable], ['color_id'])
                ->join(['v' => $valueTable], 'v.option_type_id = m.option_type_id', ['option_id'])
                ->join(['i' => $this->getTable(self::ITEM_TABLE)], 'i.item_id = m.color_id', ['name'])
                ->where('v.option_id IN (?)', $affected)
        );
        $seen = [];
        foreach ($members as $member) {
            $colorId = (int)$member['color_id'];
            $name = mb_strtolower($renamed[$colorId] ?? (string)$member['name']);
            $optionId = (int)$member['option_id'];
            if (isset($seen[$optionId][$name]) && $seen[$optionId][$name] !== $colorId) {
                throw new LocalizedException(
                    __(
                        'Cannot save: after this rename a product option (ID %1) would hold two colors named "%2". '
                        . 'A product option may use each color name only once.',
                        $optionId,
                        $renamed[$colorId] ?? $member['name']
                    )
                );
            }
            $seen[$optionId][$name] = $colorId;
        }
    }

    /**
     * Keep the stored titles of linked product option values in step with the color's name.
     *
     * @param int $colorId
     * @param string $name
     * @return void
     */
    private function syncOptionValueTitles(int $colorId, string $name): void
    {
        $connection = $this->getConnection();
        $ids = $connection->fetchCol(
            $connection->select()
                ->from($this->getTable(self::VALUE_MAP_TABLE), ['option_type_id'])
                ->where('color_id = ?', $colorId)
        );
        if ($ids) {
            $connection->update($this->getTable(self::TYPE_TITLE_TABLE), ['title' => $name], ['option_type_id IN (?)' => $ids]);
        }
    }
}
