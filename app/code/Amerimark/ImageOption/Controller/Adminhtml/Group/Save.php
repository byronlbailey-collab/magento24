<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Controller\Adminhtml\Group;

use Amerimark\ImageOption\Model\GroupFactory;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractGroup implements HttpPostActionInterface
{
    private const FILE_NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*\.(jpe?g|gif|png)$/i';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param GroupFactory $groupFactory
     * @param ImageUploader $imageUploader
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly GroupFactory $groupFactory,
        private readonly ImageUploader $imageUploader,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }
        $id = (int)($data['group_id'] ?? 0);
        try {
            $group = $this->groupFactory->create();
            $currentFiles = [];
            if ($id) {
                $group->load($id);
                if (!$group->getId()) {
                    throw new LocalizedException(__('This image option group no longer exists.'));
                }
                $currentFiles = array_column($group->getResource()->getItemsData($id), 'image');
            }
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                throw new LocalizedException(__('Please enter a group name.'));
            }
            $items = $this->prepareItems((array)($data['items'] ?? []), $currentFiles);
            if (!$items) {
                throw new LocalizedException(__('Add at least one image to the group.'));
            }
            $group->setData('name', $name);
            $group->setData('is_active', empty($data['is_active']) ? 0 : 1);
            $group->setData('items', $items);
            $group->save();
            $this->messageManager->addSuccessMessage(__('The image option group was saved.'));
            $this->dataPersistor->clear('amerimark_image_group');
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['group_id' => $group->getId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('amerimark_image_group', $data);
            return $redirect->setPath('*/*/edit', $id ? ['group_id' => $id] : []);
        }
    }

    /**
     * Validate the submitted rows and move newly uploaded files out of the tmp directory.
     *
     * File names come from the browser, so only plain image file names are accepted: new files must be in the
     * upload tmp directory, existing ones must already belong to this group.
     *
     * @param array $rows
     * @param string[] $currentFiles
     * @return array[]
     * @throws LocalizedException
     */
    private function prepareItems(array $rows, array $currentFiles): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (!empty($row['is_delete'])) {
                continue;
            }
            $label = trim((string)($row['label'] ?? ''));
            if ($label === '') {
                throw new LocalizedException(__('Every image needs a label.'));
            }
            $image = $row['image'][0] ?? null;
            $file = is_array($image) ? (string)($image['name'] ?? '') : '';
            if (!preg_match(self::FILE_NAME_PATTERN, $file)) {
                throw new LocalizedException(__('Every row needs a valid image (jpg, jpeg, gif or png).'));
            }
            if (isset($image['tmp_name'])) {
                $file = basename($this->imageUploader->moveFileFromTmp($file, true));
            } elseif (!in_array($file, $currentFiles, true)) {
                throw new LocalizedException(__('Unknown image "%1". Please upload it again.', $file));
            }
            $items[] = [
                'item_id' => (int)($row['item_id'] ?? 0),
                'label' => $label,
                'sku' => trim((string)($row['sku'] ?? '')),
                'image' => $file,
                'sort_order' => (int)($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']),
            ];
        }
        return $items;
    }
}
