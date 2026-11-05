<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Catalog;

use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Catalog\Model\Product\Option;

/**
 * Normalises the Image Group settings before an option is saved.
 *
 * The product form posts every option field, so options of other types arrive with image_group_id = '' (an empty
 * string is not a valid value for the nullable foreign key column), and options whose type was changed away from
 * Image Group would keep stale settings.
 */
class OptionNormalizePlugin
{
    /**
     * @param Option $subject
     * @return void
     */
    public function beforeBeforeSave(Option $subject): void
    {
        if ($subject->getData('type') !== ImageGroup::OPTION_TYPE) {
            $subject->setData('image_group_id', null);
            $subject->setData('image_allow_upload', 0);
            $subject->setData('image_upload_ext', null);
            return;
        }
        $groupId = (int)$subject->getData('image_group_id');
        $subject->setData('image_group_id', $groupId > 0 ? $groupId : null);
        $allow = !empty($subject->getData('image_allow_upload')) ? 1 : 0;
        $subject->setData('image_allow_upload', $allow);
        $subject->setData('image_upload_ext', $this->normalizeExtensions((string)$subject->getData('image_upload_ext')));
    }

    /**
     * Lower-case, de-duplicated, comma separated extensions without dots or blanks.
     *
     * @param string $value
     * @return string|null
     */
    private function normalizeExtensions(string $value): ?string
    {
        $parts = preg_split('/[\s,;]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_unique(array_map(fn (string $p) => ltrim($p, '.'), $parts));
        return $parts ? implode(', ', $parts) : null;
    }
}
