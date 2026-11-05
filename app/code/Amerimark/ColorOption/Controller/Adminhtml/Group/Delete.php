<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Controller\Adminhtml\Group;

use Amerimark\ColorOption\Model\GroupFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Delete extends AbstractGroup implements HttpPostActionInterface
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param GroupFactory $groupFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly GroupFactory $groupFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('group_id');
        try {
            $group = $this->groupFactory->create()->load($id);
            if (!$group->getId()) {
                throw new LocalizedException(__('This color option group no longer exists.'));
            }
            $group->delete();
            $this->messageManager->addSuccessMessage(__('The color option group was deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['group_id' => $id]);
        }
        return $redirect->setPath('*/*/');
    }
}
