<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Catalog\Ui;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;

/**
 * Adds the Color option to the product form's Customizable Options panel: a Color Group field and a grid of the
 * option's colors (each with the same price, price type, SKU and sort order fields a drop-down value has).
 */
class CustomOptionsModifierPlugin
{
    public const FIELD_GROUP = 'color_group_id';
    public const GRID_COLORS = 'color_values';

    private const OPTION_PATH = ['custom_options', 'children', 'options', 'children', 'record', 'children',
        'container_option', 'children'];

    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
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
            $option[CustomOptions::CONTAINER_COMMON_NAME]['children'],
            $option[CustomOptions::GRID_TYPE_SELECT_NAME]['children']['record']['children']
        )) {
            return $meta;
        }

        $catalog = $this->colors->getCatalog();
        $option[CustomOptions::CONTAINER_TYPE_STATIC_NAME]['children'][self::FIELD_GROUP] =
            $this->groupField($catalog['groups']);
        $option[self::GRID_COLORS] = $this->colorsGrid(
            $option[CustomOptions::GRID_TYPE_SELECT_NAME],
            $catalog['colors']
        );

        $config = &$option[CustomOptions::CONTAINER_COMMON_NAME]['children'][CustomOptions::FIELD_TYPE_NAME]
            ['arguments']['data']['config'];
        $config['groupsConfig']['color_swatch'] = [
            'values' => [OptionColors::OPTION_TYPE],
            'indexes' => [
                CustomOptions::CONTAINER_TYPE_STATIC_NAME,
                self::FIELD_GROUP,
                self::GRID_COLORS,
            ],
        ];
        return $meta;
    }

    /**
     * Gives each Color option's rows the color they stand for (the core only loads them as drop-down values).
     *
     * @param CustomOptions $subject
     * @param array $data
     * @return array
     */
    public function afterModifyData(CustomOptions $subject, array $data): array
    {
        foreach ($data as &$productData) {
            $options = &$productData[CustomOptions::DATA_SOURCE_DEFAULT][CustomOptions::GRID_OPTIONS_NAME];
            if (!is_array($options)) {
                continue;
            }
            $valueIds = [];
            foreach ($options as $optionData) {
                if (($optionData['type'] ?? null) === OptionColors::OPTION_TYPE) {
                    $valueIds = array_merge(
                        $valueIds,
                        array_column($optionData[CustomOptions::GRID_TYPE_SELECT_NAME] ?? [], 'option_type_id')
                    );
                }
            }
            $colorIds = $this->colors->getColorIdsByValueIds($valueIds);
            foreach ($options as &$optionData) {
                if (($optionData['type'] ?? null) !== OptionColors::OPTION_TYPE) {
                    continue;
                }
                $rows = [];
                foreach ($optionData[CustomOptions::GRID_TYPE_SELECT_NAME] ?? [] as $row) {
                    $row['color_id'] = (string)($colorIds[(int)($row['option_type_id'] ?? 0)] ?? '');
                    $rows[] = $row;
                }
                $optionData[self::GRID_COLORS] = $rows;
            }
            unset($optionData);
        }
        return $data;
    }

    /**
     * @param array[] $groups
     * @return array
     */
    private function groupField(array $groups): array
    {
        $options = [];
        foreach ($groups as $group) {
            $options[] = [
                'value' => (string)$group['group_id'],
                'label' => $group['name'] . ($group['is_active'] ? '' : ' (' . __('inactive') . ')'),
            ];
        }
        return ['arguments' => ['data' => ['config' => [
            'label' => __('Color Group'),
            'componentType' => Field::NAME,
            'formElement' => Select::NAME,
            'dataScope' => self::FIELD_GROUP,
            'dataType' => Text::NAME,
            'sortOrder' => 10,
            'options' => $options,
            'caption' => __('-- Please select --'),
            'notice' => __('Manage groups under Catalog > Inventory > Color Option Groups.'),
            'validation' => ['required-entry' => true],
        ]]]];
    }

    /**
     * The Color grid: a copy of the core drop-down values grid whose title column is replaced by a color chooser.
     *
     * @param array $selectGrid
     * @param array $colorsByGroup
     * @return array
     */
    private function colorsGrid(array $selectGrid, array $colorsByGroup): array
    {
        $grid = $selectGrid;
        $grid['arguments']['data']['config']['addButtonLabel'] = __('Add Color');

        $record = &$grid['children']['record']['children'];
        $record = ['color_id' => ['arguments' => ['data' => ['config' => [
            'label' => __('Color'),
            'componentType' => Field::NAME,
            'formElement' => Select::NAME,
            'component' => 'Amerimark_ColorOption/js/form/element/color-select',
            'elementTmpl' => 'Amerimark_ColorOption/form/element/color-select',
            'dataScope' => 'color_id',
            'dataType' => Text::NAME,
            'sortOrder' => 10,
            'colorsByGroup' => $colorsByGroup,
            'options' => [],
            'caption' => __('-- Choose a color --'),
            'validation' => ['required-entry' => true],
        ]]]]] + array_diff_key($record, ['title' => true]);
        return $grid;
    }
}
