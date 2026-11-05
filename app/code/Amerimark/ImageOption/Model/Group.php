<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model;

use Amerimark\ImageOption\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Image option group: a named, admin-managed list of images shoppers can pick from.
 *
 * @method int|null getGroupId()
 * @method string getName()
 * @method bool|int getIsActive()
 */
class Group extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(GroupResource::class);
    }
}
