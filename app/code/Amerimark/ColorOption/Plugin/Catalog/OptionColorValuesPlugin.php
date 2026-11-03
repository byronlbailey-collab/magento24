<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Catalog;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Model\Product\Option;

/**
 * Turns the Color option's posted rows (color_values) into the regular select-option values the core saves.
 *
 * A color option is a select-type option whose values each point at a color of the option's color group: the title is
 * the color's name, price/price type/SKU/sort order work as for a drop-down. The product form posts the rows as
 * color_values[i][color_id|price|price_type|sku|sort_order|option_type_id|is_delete]; here they become "values" before
 * validation, so the core's own value saving does the rest (see ColorValueMapPlugin for the color link).
 *
 * It also clears the color settings of options that are not (or are no longer) Color options.
 */
class OptionColorValuesPlugin
{
    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
    {
    }

    /**
     * Runs before validation, which needs the values.
     *
     * @param Option $subject
     * @return void
     */
    public function beforeValidateBeforeSave(Option $subject): void
    {
        $this->transform($subject);
    }

    /**
     * Normalises the stored group id (the form posts '' for options of other types).
     *
     * @param Option $subject
     * @return void
     */
    public function beforeBeforeSave(Option $subject): void
    {
        $this->transform($subject);
        if ($subject->getData('type') !== OptionColors::OPTION_TYPE) {
            $subject->setData('color_group_id', null);
            if ($subject->getId()) {
                $this->colors->deleteMappingsOfOption((int)$subject->getId());
            }
            return;
        }
        $groupId = (int)$subject->getData('color_group_id');
        $subject->setData('color_group_id', $groupId > 0 ? $groupId : null);
    }

    /**
     * @param Option $option
     * @return void
     */
    private function transform(Option $option): void
    {
        if ($option->getData('type') !== OptionColors::OPTION_TYPE) {
            return;
        }
        $rows = $option->getData('color_values');
        if (!is_array($rows)) {
            return;
        }
        // validation and saving both call this; do the work once per set of posted rows
        $fingerprint = md5(json_encode($rows));
        if ($option->getData('color_values_applied') === $fingerprint) {
            return;
        }

        $colors = $this->colors->getColors(array_column($rows, 'color_id'));
        $storeId = (int)$option->getData('store_id');
        $values = [];
        $kept = [];
        foreach ($rows as $row) {
            $valueId = (int)($row['option_type_id'] ?? 0);
            if (!empty($row['is_delete'])) {
                if ($valueId > 0) {
                    $values[] = ['option_type_id' => $valueId, 'is_delete' => 1];
                    $kept[$valueId] = true;
                }
                continue;
            }
            $colorId = (int)($row['color_id'] ?? 0);
            $value = [
                'color_id' => $colorId,
                'title' => $colors[$colorId]['name'] ?? '',
                'price' => $row['price'] ?? '',
                'price_type' => ($row['price_type'] ?? '') ?: 'fixed',
                'sku' => $row['sku'] ?? '',
                'sort_order' => (int)($row['sort_order'] ?? 0),
                'is_delete' => 0,
            ];
            if ($storeId > 0) {
                $value['is_use_default'] = 1;
            }
            if ($valueId > 0) {
                $value['option_type_id'] = $valueId;
                $kept[$valueId] = true;
            }
            $values[] = $value;
        }

        // values the option had before (e.g. from another option type) that the form no longer lists go away
        if ($option->getId()) {
            foreach ($this->colors->getValueIdsOfOption((int)$option->getId()) as $existingId) {
                if (!isset($kept[$existingId])) {
                    $values[] = ['option_type_id' => $existingId, 'is_delete' => 1];
                }
            }
        }

        $option->setData('values', $values);
        $option->setData('color_values_applied', $fingerprint);
    }
}
