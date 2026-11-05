<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\ResourceModel\Group;

use Amerimark\ImageOption\Model\Group;
use Amerimark\ImageOption\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'group_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Group::class, GroupResource::class);
    }
}
