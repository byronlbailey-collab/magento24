<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Block\Product\View\Options;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup as ImageGroupType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;

/**
 * Product page renderer of an Image Group option (the product options block hands it the option and product).
 *
 * @method Option getOption()
 * @method Product getProduct()
 */
class ImageGroup extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Amerimark_ImageOption::product/view/options/type/image_group.phtml';

    /**
     * @param Template\Context $context
     * @param GroupItems $groupItems
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly GroupItems $groupItems,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Selectable images.
     *
     * @return array[]
     */
    public function getItems(): array
    {
        return $this->groupItems->getItemsForOption($this->getOption());
    }

    /**
     * Whether the shopper can upload their own file.
     *
     * @return bool
     */
    public function isUploadAllowed(): bool
    {
        return (bool)$this->getOption()->getData('image_allow_upload');
    }

    /**
     * Comma separated list of the extensions the upload accepts.
     *
     * @return string
     */
    public function getUploadExtensions(): string
    {
        $ext = trim((string)$this->getOption()->getData('image_upload_ext'));
        return $ext !== '' ? $ext : ImageGroupType::DEFAULT_UPLOAD_EXTENSIONS;
    }

    /**
     * Value for the file input's accept attribute, e.g. ".png,.jpg".
     *
     * @return string
     */
    public function getUploadAccept(): string
    {
        $parts = preg_split('/[\s,;]+/', strtolower($this->getUploadExtensions()), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode(',', array_map(static fn (string $e) => '.' . ltrim($e, '.'), $parts));
    }

    /**
     * Value of the option from the cart/wishlist item being edited (or the posted value after a failed add).
     *
     * @return mixed
     */
    private function getPreconfiguredValue()
    {
        return $this->getProduct()->getPreconfiguredValues()->getData('options/' . $this->getOption()->getId());
    }

    /**
     * Image id currently selected, if any.
     *
     * @return int
     */
    public function getSelectedImageId(): int
    {
        $value = $this->getPreconfiguredValue();
        return ImageGroupType::isImageValue($value) ? (int)$value : 0;
    }

    /**
     * Info of a previously uploaded file that is being edited, if any.
     *
     * @return DataObject
     */
    public function getFileInfo(): DataObject
    {
        $value = $this->getPreconfiguredValue();
        return new DataObject(is_array($value) ? $value : []);
    }

    /**
     * Widget configuration for the template.
     *
     * @return array
     */
    public function getWidgetConfig(): array
    {
        return [
            'items' => array_map(
                static fn (array $i) => [
                    'id' => $i['item_id'], 'label' => $i['label'], 'thumb' => $i['thumb'], 'url' => $i['url'],
                ],
                $this->getItems()
            ),
            'selected' => $this->getSelectedImageId(),
            'required' => (bool)$this->getOption()->getIsRequire(),
            'uploadAllowed' => $this->isUploadAllowed(),
            'uploadExtensions' => $this->isUploadAllowed() ? $this->getUploadExtensions() : '',
            'hasSavedUpload' => $this->getFileInfo()->hasData(),
            'savedUploadTitle' => (string)$this->getFileInfo()->getTitle(),
            'optionId' => (int)$this->getOption()->getId(),
        ];
    }

    /**
     * Widget configuration as the data-mage-init attribute value.
     *
     * @return string JSON
     */
    public function getMageInit(): string
    {
        return $this->json->serialize(['Amerimark_ImageOption/js/image-picker' => $this->getWidgetConfig()]);
    }
}
