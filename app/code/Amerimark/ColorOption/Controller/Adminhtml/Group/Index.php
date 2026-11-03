<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Controller\Adminhtml\Group;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Index extends AbstractGroup implements HttpGetActionInterface
{
    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Amerimark_ColorOption::groups');
        $page->getConfig()->getTitle()->prepend(__('Color Option Groups'));
        return $page;
    }
}
