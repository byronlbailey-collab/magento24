<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\Group;

use Amerimark\ColorOption\Model\ResourceModel\Group as GroupResource;
use Amerimark\ColorOption\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Form data for the group edit page, including the color rows.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    private array $loaded = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param GroupResource $groupResource
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly GroupResource $groupResource,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loaded) {
            return $this->loaded;
        }
        foreach ($this->collection->getItems() as $group) {
            $groupId = (int)$group->getId();
            $row = $group->getData();
            $row['items'] = $this->groupResource->getItemsData($groupId);
            $this->loaded[$groupId] = $row;
        }
        // Re-display what the admin submitted when a save failed.
        $persisted = $this->dataPersistor->get('amerimark_color_group');
        if (!empty($persisted)) {
            $groupId = (int)($persisted['group_id'] ?? 0);
            $this->loaded[$groupId ?: ''] = $persisted;
            $this->dataPersistor->clear('amerimark_color_group');
        }
        return $this->loaded;
    }
}
