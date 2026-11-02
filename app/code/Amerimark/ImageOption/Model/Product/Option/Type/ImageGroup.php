<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\Product\Option\Type;

use Amerimark\ImageOption\Model\GroupItems;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Helper\Product\Validator\ProductOptionValidator;
use Magento\Catalog\Model\Product\Option\Type\File;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorFile;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorInfo;
use Magento\Catalog\Model\Product\Option\UrlBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Quote\Model\Quote\Item\OptionFactory;

/**
 * "Image Group" custom option: the shopper picks an image from the group the option points at or, when the option
 * allows it, uploads their own file. Extends the core file option so uploads reuse its validation, storage and
 * quote-to-order copy.
 *
 * Stored value (quote item option): the image id as a plain digit string for a chosen image, or the core file
 * option's serialized upload info for an upload. Chosen images are referenced live (so an image that gets disabled
 * or deleted is caught again at checkout); the order snapshot is taken when the order is placed.
 */
class ImageGroup extends File
{
    public const OPTION_TYPE = 'image_group';
    public const DEFAULT_UPLOAD_EXTENSIONS = 'png, jpg, jpeg, gif, pdf';

    /**
     * Uploaded files are downloaded through this module's route: the core route refuses any non-"file" option.
     *
     * @var string
     */
    protected $_customOptionDownloadUrl = 'amerimark_imageoption/download/upload';

    /**
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param OptionFactory $itemOptionFactory
     * @param Database $coreFileStorageDatabase
     * @param ValidatorInfo $validatorInfo
     * @param ValidatorFile $validatorFile
     * @param UrlBuilder $urlBuilder
     * @param Escaper $escaper
     * @param GroupItems $groupItems
     * @param array $data
     * @param Filesystem|null $filesystem
     * @param Json|null $serializer
     * @param ProductHelper|null $productHelper
     * @param ProductOptionValidator|null $productOptionValidator
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        OptionFactory $itemOptionFactory,
        Database $coreFileStorageDatabase,
        ValidatorInfo $validatorInfo,
        ValidatorFile $validatorFile,
        UrlBuilder $urlBuilder,
        Escaper $escaper,
        private readonly GroupItems $groupItems,
        array $data = [],
        ?Filesystem $filesystem = null,
        ?Json $serializer = null,
        ?ProductHelper $productHelper = null,
        ?ProductOptionValidator $productOptionValidator = null
    ) {
        parent::__construct(
            $checkoutSession,
            $scopeConfig,
            $itemOptionFactory,
            $coreFileStorageDatabase,
            $validatorInfo,
            $validatorFile,
            $urlBuilder,
            $escaper,
            $data,
            $filesystem,
            $serializer,
            $productHelper,
            $productOptionValidator
        );
    }

    /**
     * Whether the value is a chosen image id (as opposed to the serialized info of an uploaded file).
     *
     * @param mixed $value
     * @return bool
     */
    public static function isImageValue($value): bool
    {
        return (is_string($value) || is_int($value)) && ctype_digit((string)$value);
    }

    /**
     * Whether the option lets the shopper upload their own file.
     *
     * @return bool
     */
    public function isUploadAllowed(): bool
    {
        return (bool)$this->getOption()->getData('image_allow_upload');
    }

    /**
     * Validate what the shopper submitted: a chosen image id, an uploaded file, or an earlier upload to keep.
     *
     * @param array $values All product option values, i.e. array (option_id => mixed, option_id => mixed...)
     * @return $this
     * @throws LocalizedException
     */
    public function validateUserValue($values)
    {
        $option = $this->getOption();
        $raw = $values[$option->getId()] ?? null;

        if ($this->isUploadAllowed()) {
            // The core file validation reads its allowed extensions from file_extension.
            $option->setData('file_extension', $this->getUploadExtensions());
            $option->setData('image_size_x', 0);
            $option->setData('image_size_y', 0);
        }

        if (!($this->isUploadAllowed() && $this->hasUploadedFile())
            && !is_array($raw)
            && is_scalar($raw)
            && trim((string)$raw) !== ''
        ) {
            return $this->validateImageChoice((string)$raw);
        }

        if (!$this->isUploadAllowed()) {
            $this->_checkoutSession->setUseNotice(false);
            $this->setIsValid(false);
            $this->setUserValue(null);
            if ($option->getIsRequire() && !$this->getSkipCheckRequiredOption()) {
                throw new LocalizedException(
                    __("The product's required option(s) weren't entered. Make sure the options are entered and try again.")
                );
            }
            return $this;
        }
        return parent::validateUserValue($values);
    }

    /**
     * Prepare the option value for the cart.
     *
     * @return string|null
     */
    public function prepareForCart()
    {
        $value = $this->getUserValue();
        if (!static::isImageValue($value)) {
            return parent::prepareForCart();
        }
        $optionId = $this->getOption()->getId();
        $buyRequest = $this->getRequest();
        $requestOptions = $buyRequest->getOptions();
        $requestOptions[$optionId] = (string)$value;
        $buyRequest->setOptions($requestOptions);
        $buyRequest->unsetData('options_' . $optionId . '_file_action');
        return $this->getIsValid() ? (string)$value : null;
    }

    /**
     * Cart/checkout HTML for the stored value.
     *
     * @param string $optionValue
     * @return string
     */
    public function getFormattedOptionValue($optionValue)
    {
        if (!static::isImageValue($optionValue)) {
            return parent::getFormattedOptionValue($optionValue);
        }
        return $this->renderImage((int)$optionValue);
    }

    /**
     * Plain-text value (label) for text-only places.
     *
     * @param string $optionValue
     * @return string
     */
    public function getPrintableOptionValue($optionValue)
    {
        if (!static::isImageValue($optionValue)) {
            return parent::getPrintableOptionValue($optionValue);
        }
        $item = $this->groupItems->findItem($this->getOption(), (int)$optionValue);
        return $item ? $item['label'] : (string)__('Image no longer available');
    }

    /**
     * Value as a user-editable string.
     *
     * @param string $optionValue
     * @return string
     */
    public function getEditableOptionValue($optionValue)
    {
        return static::isImageValue($optionValue)
            ? $this->getPrintableOptionValue($optionValue)
            : parent::getEditableOptionValue($optionValue);
    }

    /**
     * Turn an edited string (an image label) back into the stored value.
     *
     * @param string $optionValue
     * @param array $productOptionValues
     * @return string|null
     */
    public function parseOptionValue($optionValue, $productOptionValues)
    {
        $text = mb_strtolower(trim((string)$optionValue));
        foreach ($this->groupItems->getItemsForOption($this->getOption()) as $item) {
            if ($text !== '' && mb_strtolower($item['label']) === $text) {
                return (string)$item['item_id'];
            }
        }
        return parent::parseOptionValue($optionValue, $productOptionValues);
    }

    /**
     * Value as it goes back into the buy request (reorder / edit).
     *
     * @param string $optionValue
     * @return string|array|null
     */
    public function prepareOptionValueForRequest($optionValue)
    {
        return static::isImageValue($optionValue)
            ? (string)$optionValue
            : parent::prepareOptionValueForRequest($optionValue);
    }

    /**
     * Customized view for order/invoice/credit memo displays: the order-time snapshot when there is one.
     *
     * @param array $optionInfo
     * @return string|void
     */
    public function getCustomizedView($optionInfo)
    {
        if (isset($optionInfo['option_value']) && static::isImageValue($optionInfo['option_value'])) {
            return $optionInfo['value'] ?? $this->renderImage((int)$optionInfo['option_value']);
        }
        return parent::getCustomizedView($optionInfo);
    }

    /**
     * Image choices have nothing to copy from quote to order; uploads use the core file copy.
     *
     * @return $this
     */
    public function copyQuoteToOrder()
    {
        $quoteOption = $this->getConfigurationItemOption();
        if ($quoteOption && static::isImageValue($quoteOption->getValue())) {
            return $this;
        }
        return parent::copyQuoteToOrder();
    }

    /**
     * The chosen image's SKU is appended to the line item SKU like the SKU of a drop-down value.
     *
     * @param string $optionValue
     * @param string $skuDelimiter
     * @return string
     */
    public function getOptionSku($optionValue, $skuDelimiter)
    {
        if (!static::isImageValue($optionValue)) {
            return '';
        }
        $item = $this->groupItems->findItem($this->getOption(), (int)$optionValue);
        return $item ? $item['sku'] : '';
    }

    /**
     * The image option has no price.
     *
     * @param string $optionValue
     * @param float $basePrice
     * @return float
     */
    public function getOptionPrice($optionValue, $basePrice)
    {
        return 0.0;
    }

    /**
     * @param string $raw
     * @return $this
     * @throws LocalizedException
     */
    private function validateImageChoice(string $raw): self
    {
        $this->_checkoutSession->setUseNotice(false);
        $option = $this->getOption();
        $item = static::isImageValue($raw) ? $this->groupItems->findActiveItem($option, (int)$raw) : null;
        if (!$item) {
            $this->setIsValid(false);
            throw new LocalizedException(
                __('Please choose one of the available images for "%1".', $option->getTitle())
            );
        }
        $this->setIsValid(true);
        $this->setUserValue((string)$item['item_id']);
        return $this;
    }

    /**
     * Whether a file was posted for this option.
     *
     * @return bool
     */
    private function hasUploadedFile(): bool
    {
        // phpcs:ignore Magento2.Security.Superglobal -- the core file option reads $_FILES the same way
        $file = $_FILES['options_' . $this->getOption()->getId() . '_file'] ?? null;
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return string
     */
    private function getUploadExtensions(): string
    {
        $extensions = trim((string)$this->getOption()->getData('image_upload_ext'));
        return $extensions !== '' ? $extensions : self::DEFAULT_UPLOAD_EXTENSIONS;
    }

    /**
     * Thumbnail + label. Uses only span/a markup with an inline style so the cart page's HTML filter keeps it.
     *
     * @param int $itemId
     * @return string
     */
    private function renderImage(int $itemId): string
    {
        $item = $this->groupItems->findItem($this->getOption(), $itemId);
        if (!$item) {
            return '<span class="amerimark-image-option amerimark-image-option--unavailable">'
                . $this->_escaper->escapeHtml(__('Image no longer available')) . '</span>';
        }
        $style = sprintf(
            'display:inline-block;width:40px;height:40px;margin-right:8px;vertical-align:middle;'
            . 'background:center/contain no-repeat url(%s)',
            $this->_escaper->escapeUrl($item['thumb'])
        );
        return '<span class="amerimark-image-option">'
            . '<span class="amerimark-image-option__thumb" style="' . $this->_escaper->escapeHtmlAttr($style) . '"></span>'
            . '<span class="amerimark-image-option__label">' . $this->_escaper->escapeHtml($item['label']) . '</span>'
            . '</span>';
    }
}
