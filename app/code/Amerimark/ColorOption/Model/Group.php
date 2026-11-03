<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model;

use Amerimark\ColorOption\Model\ResourceModel\Group as GroupResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Color option group: a named, admin-managed list of colors shoppers can pick from.
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
