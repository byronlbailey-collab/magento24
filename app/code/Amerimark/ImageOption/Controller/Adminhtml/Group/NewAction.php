<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Controller\Adminhtml\Group;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

class NewAction extends AbstractGroup implements HttpGetActionInterface
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Forward
     */
    public function execute()
    {
        return $this->forwardFactory->create()->forward('edit');
    }
}
