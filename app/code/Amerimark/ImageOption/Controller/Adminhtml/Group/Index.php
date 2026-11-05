<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Controller\Adminhtml\Group;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;

class Index extends AbstractGroup implements HttpGetActionInterface
{
    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $page */
        $page = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Amerimark_ImageOption::groups');
        $page->getConfig()->getTitle()->prepend(__('Image Option Groups'));
        return $page;
    }
}
