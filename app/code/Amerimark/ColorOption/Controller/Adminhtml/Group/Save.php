<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Controller\Adminhtml\Group;

use Amerimark\ColorOption\Model\GroupFactory;
use Amerimark\ColorOption\Model\HexColor;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractGroup implements HttpPostActionInterface
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param GroupFactory $groupFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly GroupFactory $groupFactory,
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
            if ($id) {
                $group->load($id);
                if (!$group->getId()) {
                    throw new LocalizedException(__('This color option group no longer exists.'));
                }
            }
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                throw new LocalizedException(__('Please enter a group name.'));
            }
            $items = $this->prepareItems((array)($data['items'] ?? []));
            if (!$items) {
                throw new LocalizedException(__('Add at least one color to the group.'));
            }
            $group->setData('name', $name);
            $group->setData('is_active', empty($data['is_active']) ? 0 : 1);
            $group->setData('items', $items);
            $group->save();
            $this->messageManager->addSuccessMessage(__('The color option group was saved.'));
            $this->dataPersistor->clear('amerimark_color_group');
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['group_id' => $group->getId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('amerimark_color_group', $data);
            return $redirect->setPath('*/*/edit', $id ? ['group_id' => $id] : []);
        }
    }

    /**
     * Validate the submitted rows (names may repeat inside a group).
     *
     * @param array $rows
     * @return array[]
     * @throws LocalizedException
     */
    private function prepareItems(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (!empty($row['is_delete'])) {
                continue;
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                throw new LocalizedException(__('Every color needs a name.'));
            }
            $hex = HexColor::normalize($row['hex'] ?? '');
            if ($hex === null) {
                throw new LocalizedException(
                    __('The display color of "%1" is not valid. Use a hex value such as #C8102E, or the picker.', $name)
                );
            }
            $items[] = [
                'item_id' => (int)($row['item_id'] ?? 0),
                'name' => $name,
                'hex' => $hex,
                'swatch_style' => (string)($row['swatch_style'] ?? ''),
                'pms' => trim((string)($row['pms'] ?? '')),
                'comment' => trim((string)($row['comment'] ?? '')),
                'sort_order' => (int)($row['sort_order'] ?? 0),
                'is_active' => !empty($row['is_active']),
            ];
        }
        // rows are ordered by their dragged position; store 0, 1, 2... so the order is always clean
        usort($items, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        foreach ($items as $position => &$item) {
            $item['sort_order'] = $position;
        }
        return $items;
    }
}
