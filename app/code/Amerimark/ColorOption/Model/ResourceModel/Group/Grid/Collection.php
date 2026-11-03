<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\ResourceModel\Group\Grid;

use Amerimark\ColorOption\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Admin grid collection: groups with their color count and the number of product options that use them.
 */
class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->columns([
            'items_count' => new \Zend_Db_Expr(sprintf(
                '(SELECT COUNT(*) FROM %s i WHERE i.group_id = main_table.group_id)',
                $this->getTable(GroupResource::ITEM_TABLE)
            )),
            'usage_count' => new \Zend_Db_Expr(sprintf(
                '(SELECT COUNT(*) FROM %s o WHERE o.color_group_id = main_table.group_id)',
                $this->getTable(GroupResource::OPTION_TABLE)
            )),
        ]);
        return $this;
    }

    /**
     * Allow filtering by the computed columns.
     *
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (in_array($field, ['items_count', 'usage_count'], true)) {
            foreach ($this->getSelect()->getPart(\Magento\Framework\DB\Select::COLUMNS) as $column) {
                if ($column[2] === $field) {
                    $this->getSelect()->having($this->_getConditionSql($column[1], $condition));
                    return $this;
                }
            }
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
