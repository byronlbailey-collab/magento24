<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;

abstract class AbstractGroup extends Action
{
    public const ADMIN_RESOURCE = 'Amerimark_ColorOption::groups';
}
