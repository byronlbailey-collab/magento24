<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Catalog\Ui;

use Amerimark\ImageOption\Model\Config\Source\Groups;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;

/**
 * Adds the Image Group option's settings to the product form's Customizable Options panel and tells the type
 * selector which fields to show for it.
 */
class CustomOptionsModifierPlugin
{
    public const FIELD_GROUP = 'image_group_id';
    public const FIELD_ALLOW_UPLOAD = 'image_allow_upload';
    public const FIELD_UPLOAD_EXT = 'image_upload_ext';
    public const DEFAULT_UPLOAD_EXT = 'png, jpg, jpeg, gif, pdf';

    private const OPTION_PATH = ['custom_options', 'children', 'options', 'children', 'record', 'children',
        'container_option', 'children'];

    /**
     * @param Groups $groups
     */
    public function __construct(private readonly Groups $groups)
    {
    }

    /**
     * @param CustomOptions $subject
     * @param array $meta
     * @return array
     */
    public function afterModifyMeta(CustomOptions $subject, array $meta): array
    {
        $option = &$meta;
        foreach (self::OPTION_PATH as $key) {
            if (!isset($option[$key])) {
                return $meta;
            }
            $option = &$option[$key];
        }
        if (!isset(
            $option[CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'],
            $option[CustomOptions::CONTAINER_COMMON_NAME]['children']
        )) {
            return $meta;
        }

        $option[CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'][self::FIELD_GROUP] = $this->groupField(10);
        $option[CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'][self::FIELD_ALLOW_UPLOAD] = $this->uploadField(20);
        $option[CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'][self::FIELD_UPLOAD_EXT] = $this->extensionsField(30);

        $config = &$option[CustomOptions::CONTAINER_COMMON_NAME]['children'][CustomOptions::FIELD_TYPE_NAME]
            ['arguments']['data']['config'];
        $config['groupsConfig']['image_group'] = [
            'values' => ['image_group'],
            'indexes' => [
                CustomOptions::CONTAINER_TYPE_STATIC_NAME,
                self::FIELD_GROUP,
                self::FIELD_ALLOW_UPLOAD,
                self::FIELD_UPLOAD_EXT,
            ],
        ];
        return $meta;
    }

    /**
     * @param int $sortOrder
     * @return array
     */
    private function groupField(int $sortOrder): array
    {
        return ['arguments' => ['data' => ['config' => [
            'label' => __('Image Group'),
            'componentType' => Field::NAME,
            'formElement' => Select::NAME,
            'dataScope' => self::FIELD_GROUP,
            'dataType' => Text::NAME,
            'sortOrder' => $sortOrder,
            'options' => $this->groups->toOptionArray(),
            'caption' => __('-- Please select --'),
            'notice' => __('Manage groups under Catalog > Inventory > Image Option Groups.'),
            'validation' => ['required-entry' => true],
        ]]]];
    }

    /**
     * @param int $sortOrder
     * @return array
     */
    private function uploadField(int $sortOrder): array
    {
        return ['arguments' => ['data' => ['config' => [
            'label' => __('Allow Customer Logo Upload'),
            'componentType' => Field::NAME,
            'formElement' => Checkbox::NAME,
            'dataScope' => self::FIELD_ALLOW_UPLOAD,
            'dataType' => Text::NAME,
            'sortOrder' => $sortOrder,
            'value' => '0',
            'prefer' => 'toggle',
            'valueMap' => ['true' => '1', 'false' => '0'],
            'notice' => __('Lets the shopper upload their own image instead of choosing one from the group.'),
        ]]]];
    }

    /**
     * @param int $sortOrder
     * @return array
     */
    private function extensionsField(int $sortOrder): array
    {
        return ['arguments' => ['data' => ['config' => [
            'label' => __('Allowed Upload File Extensions'),
            'componentType' => Field::NAME,
            'formElement' => Input::NAME,
            'dataScope' => self::FIELD_UPLOAD_EXT,
            'dataType' => Text::NAME,
            'sortOrder' => $sortOrder,
            'value' => self::DEFAULT_UPLOAD_EXT,
            'notice' => __('Used only when customer upload is allowed. Separate with commas, e.g. png, jpg, pdf.'),
        ]]]];
    }
}
