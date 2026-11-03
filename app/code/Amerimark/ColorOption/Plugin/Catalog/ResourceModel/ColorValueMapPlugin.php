<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Catalog\ResourceModel;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value as ValueResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Records which color each saved option value stands for (the core saves values without telling us the id of new
 * ones, so this is the one place where both the new value id and the posted color id are known).
 */
class ColorValueMapPlugin
{
    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
    {
    }

    /**
     * @param ValueResource $subject
     * @param ValueResource $result
     * @param AbstractModel $object
     * @return ValueResource
     */
    public function afterSave(ValueResource $subject, $result, AbstractModel $object)
    {
        $colorId = (int)$object->getData('color_id');
        if ($colorId > 0 && $object->getId()) {
            $this->colors->saveMapping((int)$object->getId(), $colorId);
        }
        return $result;
    }
}
