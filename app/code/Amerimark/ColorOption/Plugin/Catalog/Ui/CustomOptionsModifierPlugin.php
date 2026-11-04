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
 * Adds the Color option to the product form's Customizable Options panel: a grid of the option's colors. Each row
 * picks a color group, then a color from it (so one option can mix colors of several groups), and has the same price
 * and price type fields a drop-down value has.
 */
class CustomOptionsModifierPlugin
{
    public const FIELD_GROUP = 'color_group_id';
    public const TYPE_COMPONENT = 'Amerimark_ColorOption/js/form/element/option-type';
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
        $option[self::GRID_COLORS] = $this->colorsGrid(
            $option[CustomOptions::GRID_TYPE_SELECT_NAME],
            $catalog
        );

        // the type selector shows/hides the other groups' fields itself; this subclass also handles the color grid
        $config = &$option[CustomOptions::CONTAINER_COMMON_NAME]['children'][CustomOptions::FIELD_TYPE_NAME]
            ['arguments']['data']['config'];
        $config['component'] = self::TYPE_COMPONENT;
        $config['groupsConfig']['color_swatch'] = [
            'values' => [OptionColors::OPTION_TYPE],
            'indexes' => [],
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
            $colors = $this->colors->getColors($colorIds);
            foreach ($options as &$optionData) {
                if (($optionData['type'] ?? null) !== OptionColors::OPTION_TYPE) {
                    continue;
                }
                $rows = [];
                foreach ($optionData[CustomOptions::GRID_TYPE_SELECT_NAME] ?? [] as $row) {
                    $colorId = $colorIds[(int)($row['option_type_id'] ?? 0)] ?? 0;
                    $row['color_id'] = $colorId ? (string)$colorId : '';
                    $row[self::FIELD_GROUP] = isset($colors[$colorId]) ? (string)$colors[$colorId]['group_id'] : '';
                    $rows[] = $row;
                }
                $optionData[self::GRID_COLORS] = $rows;
            }
            unset($optionData);
        }
        return $data;
    }

    /**
     * The Color grid: a copy of the core drop-down values grid whose title and SKU columns are replaced by a group
     * chooser and a color chooser, and whose price column is narrow.
     *
     * @param array $selectGrid
     * @param array $catalog
     * @return array
     */
    private function colorsGrid(array $selectGrid, array $catalog): array
    {
        $grid = $selectGrid;
        $config = &$grid['arguments']['data']['config'];
        $config['addButtonLabel'] = __('Add Color');
        // shown by the type selector once "Color Swatches" is chosen
        $config['visible'] = false;
        unset($config);

        $groups = [];
        foreach ($catalog['groups'] as $group) {
            $groups[] = [
                'value' => (string)$group['group_id'],
                'label' => $group['name'] . ($group['is_active'] ? '' : ' (' . __('inactive') . ')'),
            ];
        }

        $record = $grid['children']['record']['children'];
        unset($record['title'], $record['sku']);
        $record['price']['arguments']['data']['config']['additionalClasses'] = ['admin__field-x-small' => true];

        $record = [
            self::FIELD_GROUP => ['arguments' => ['data' => ['config' => [
                'label' => __('Color Group'),
                'componentType' => Field::NAME,
                'formElement' => Select::NAME,
                'dataScope' => self::FIELD_GROUP,
                'dataType' => Text::NAME,
                'sortOrder' => 5,
                'options' => $groups,
                'caption' => __('-- Choose a group --'),
                'validation' => ['required-entry' => true],
            ]]]],
            'color_id' => ['arguments' => ['data' => ['config' => [
                'label' => __('Color'),
                'componentType' => Field::NAME,
                'formElement' => Select::NAME,
                'component' => 'Amerimark_ColorOption/js/form/element/color-select',
                'elementTmpl' => 'Amerimark_ColorOption/form/element/color-select',
                'dataScope' => 'color_id',
                'dataType' => Text::NAME,
                'sortOrder' => 10,
                'colorsByGroup' => $catalog['colors'],
                'options' => [],
                'caption' => __('-- Choose a color --'),
                'validation' => ['required-entry' => true],
            ]]]],
        ] + $record;
        $grid['children']['record']['children'] = $record;
        return $grid;
    }
}
