<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\Group;

use Amerimark\ImageOption\Model\ImageUrl;
use Amerimark\ImageOption\Model\ResourceModel\Group as GroupResource;
use Amerimark\ImageOption\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Form data for the group edit page, including the image rows in the shape the image uploader expects.
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
     * @param ImageUrl $imageUrl
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
        private readonly ImageUrl $imageUrl,
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
            $row['items'] = array_map(fn (array $item) => $this->formatItem($item), $this->groupResource->getItemsData($groupId));
            $this->loaded[$groupId] = $row;
        }
        // Re-display what the admin submitted when a save failed.
        $persisted = $this->dataPersistor->get('amerimark_image_group');
        if (!empty($persisted)) {
            $groupId = (int)($persisted['group_id'] ?? 0);
            $this->loaded[$groupId ?: ''] = $persisted;
            $this->dataPersistor->clear('amerimark_image_group');
        }
        return $this->loaded;
    }

    /**
     * @param array $item
     * @return array
     */
    private function formatItem(array $item): array
    {
        $item['image'] = [[
            'name' => $item['image'],
            'url' => $this->imageUrl->getUrl($item['image']),
            'type' => 'image',
        ]];
        return $item;
    }
}
