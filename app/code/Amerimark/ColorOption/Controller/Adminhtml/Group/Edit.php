<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Controller\Adminhtml\Group;

use Amerimark\ColorOption\Model\GroupFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Edit extends AbstractGroup implements HttpGetActionInterface
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
     * @return Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('group_id');
        $group = $this->groupFactory->create();
        if ($id) {
            $group->load($id);
            if (!$group->getId()) {
                $this->messageManager->addErrorMessage(__('This color option group no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Amerimark_ColorOption::groups');
        $page->getConfig()->getTitle()->prepend(
            $group->getId() ? __('Edit Color Option Group "%1"', $group->getName()) : __('New Color Option Group')
        );
        return $page;
    }
}
