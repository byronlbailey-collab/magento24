<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\Config\Source;

use Amerimark\ImageOption\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Image option groups as select options (inactive groups are marked, not hidden, so existing choices stay visible).
 */
class Groups implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        $collection = $this->collectionFactory->create()->setOrder('name', 'ASC');
        foreach ($collection as $group) {
            $label = (string)$group->getName();
            if (!$group->getIsActive()) {
                $label .= ' ' . __('(inactive)');
            }
            $options[] = ['value' => (string)$group->getId(), 'label' => $label];
        }
        return $options;
    }
}
