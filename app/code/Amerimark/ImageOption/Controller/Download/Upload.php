<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Controller\Download;

use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Sales\Model\Download;

/**
 * Download of a file a shopper uploaded for an Image Group option.
 *
 * Same contract as core's sales/download/downloadCustomOption (quote item option id + secret key), which refuses
 * every option whose type is not "file".
 */
class Upload extends Action implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param ForwardFactory $forwardFactory
     * @param Download $download
     * @param OptionFactory $quoteItemOptionFactory
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        private readonly ForwardFactory $forwardFactory,
        private readonly Download $download,
        private readonly OptionFactory $quoteItemOptionFactory,
        private readonly \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        private readonly Json $serializer
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $forward = $this->forwardFactory->create();
        $quoteOption = $this->quoteItemOptionFactory->create()->load($this->getRequest()->getParam('id'));
        if (!$quoteOption->getId()) {
            return $forward->forward('noroute');
        }
        $code = (string)$quoteOption->getCode();
        if (!str_starts_with($code, AbstractType::OPTION_PREFIX)) {
            return $forward->forward('noroute');
        }
        $optionId = substr($code, strlen(AbstractType::OPTION_PREFIX));
        if (!ctype_digit($optionId)) {
            return $forward->forward('noroute');
        }
        /** @var Option $productOption */
        $productOption = $this->productOptionFactory->create()->load((int)$optionId);
        if ($productOption->getType() !== ImageGroup::OPTION_TYPE) {
            return $forward->forward('noroute');
        }
        try {
            $info = $this->serializer->unserialize($quoteOption->getValue());
            if (!is_array($info)
                || !isset($info['secret_key'])
                || !hash_equals((string)$info['secret_key'], (string)$this->getRequest()->getParam('key'))
            ) {
                return $forward->forward('noroute');
            }
            return $this->download->createResponse($info);
        } catch (\Exception $e) {
            return $forward->forward('noroute');
        }
    }
}
