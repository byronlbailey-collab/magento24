<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Group extends AbstractDb
{
    public const TABLE = 'amerimark_image_group';
    public const ITEM_TABLE = 'amerimark_image_group_item';
    public const OPTION_TABLE = 'catalog_product_option';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, 'group_id');
    }

    /**
     * Number of product options (across all products) that use the group.
     *
     * @param int $groupId
     * @return int
     */
    public function countUsages(int $groupId): int
    {
        $connection = $this->getConnection();
        return (int)$connection->fetchOne(
            $connection->select()
                ->from($this->getTable(self::OPTION_TABLE), ['COUNT(*)'])
                ->where('image_group_id = ?', $groupId)
        );
    }

    /**
     * Items of a group as plain arrays, ordered for display.
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
     * Save the group's items: update existing rows, insert new ones, delete the ones no longer present.
     *
     * Image files are never deleted here: orders keep a reference to the image path they were placed with.
     *
     * @param AbstractModel $object
     * @return $this
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
     * @param array[] $items rows with optional item_id, and label, sku, image, sort_order, is_active
     * @return void
     */
    private function saveItems(int $groupId, array $items): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable(self::ITEM_TABLE);
        $existing = array_map('intval', $connection->fetchCol(
            $connection->select()->from($table, ['item_id'])->where('group_id = ?', $groupId)
        ));
        $keep = [];
        foreach ($items as $row) {
            $data = [
                'group_id' => $groupId,
                'label' => (string)$row['label'],
                'sku' => ($row['sku'] ?? '') !== '' ? (string)$row['sku'] : null,
                'image' => (string)$row['image'],
                'sort_order' => max(0, (int)($row['sort_order'] ?? 0)),
                'is_active' => empty($row['is_active']) ? 0 : 1,
            ];
            $itemId = (int)($row['item_id'] ?? 0);
            if ($itemId && in_array($itemId, $existing, true)) {
                $connection->update($table, $data, ['item_id = ?' => $itemId]);
                $keep[] = $itemId;
            } else {
                $connection->insert($table, $data);
                $keep[] = (int)$connection->lastInsertId($table);
            }
        }
        $remove = array_diff($existing, $keep);
        if ($remove) {
            $connection->delete($table, ['item_id IN (?)' => $remove]);
        }
    }
}
