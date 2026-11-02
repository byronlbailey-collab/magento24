<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Controller\Adminhtml\Group;

use Amerimark\ImageOption\Model\GroupFactory;
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
                $this->messageManager->addErrorMessage(__('This image option group no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Amerimark_ImageOption::groups');
        $page->getConfig()->getTitle()->prepend(
            $group->getId() ? __('Edit Image Option Group "%1"', $group->getName()) : __('New Image Option Group')
        );
        return $page;
    }
}
