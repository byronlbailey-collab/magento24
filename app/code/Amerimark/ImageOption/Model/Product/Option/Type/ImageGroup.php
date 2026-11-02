<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model\Product\Option\Type;

use Magento\Catalog\Model\Product\Option\Type\File;

/**
 * "Image Group" custom option: the shopper picks an image from the group the option points at, or (when the option
 * allows it) uploads their own file. Extends the core file option so uploads reuse its validation and storage.
 *
 * Cart behavior is added in the storefront phase.
 */
class ImageGroup extends File
{
    public const OPTION_TYPE = 'image_group';
}
