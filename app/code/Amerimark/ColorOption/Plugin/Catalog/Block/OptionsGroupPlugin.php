<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Catalog\Block;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Block\Product\View\Options;

/**
 * A Color option belongs to the select group for pricing and saving, but the product page draws it with its own
 * swatch block (the child block named "color_swatch" of the options block).
 */
class OptionsGroupPlugin
{
    /**
     * @param Options $subject
     * @param string $result
     * @param string|null $type
     * @return string
     */
    public function afterGetGroupOfOption(Options $subject, $result, $type)
    {
        return $type === OptionColors::OPTION_TYPE ? OptionColors::OPTION_TYPE : $result;
    }
}
