<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\Product\Option\Validator;

use Amerimark\ImageOption\Model\GroupFactory;
use Magento\Catalog\Model\Config\Source\Product\Options\Price;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Validator\DefaultValidator;
use Magento\Catalog\Model\ProductOptions\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;

/**
 * Validates an Image Group option when a product is saved: it needs a title and an existing group.
 *
 * Unlike the core validator it does not require a price (this option type carries none).
 */
class ImageGroup extends DefaultValidator
{
    /**
     * @param ConfigInterface $productOptionConfig
     * @param Price $priceConfig
     * @param GroupFactory $groupFactory
     * @param FormatInterface|null $localeFormat
     */
    public function __construct(
        ConfigInterface $productOptionConfig,
        Price $priceConfig,
        private readonly GroupFactory $groupFactory,
        ?FormatInterface $localeFormat = null
    ) {
        parent::__construct($productOptionConfig, $priceConfig, $localeFormat);
    }

    /**
     * @inheritdoc
     */
    public function isValid($option)
    {
        $this->_clearMessages();
        $messages = [];
        if (!$this->validateOptionRequiredFields($option)) {
            $messages['option required fields'] = 'Missed values for option required fields';
        }
        if (!$this->validateOptionType($option)) {
            $messages['option type'] = 'Invalid option type';
        }
        if (!$this->groupExists((int)$option->getData('image_group_id'))) {
            $messages['image group'] = 'Select an existing image group for the Image option';
        }
        $this->_addMessages($messages);
        return empty($messages);
    }

    /**
     * @param int $groupId
     * @return bool
     */
    private function groupExists(int $groupId): bool
    {
        return $groupId > 0 && (bool)$this->groupFactory->create()->load($groupId)->getId();
    }
}
