<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Checkout;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Checkout\CustomerData\DefaultItem;

/**
 * The mini-cart template prints option values as plain text unless the option type is "file" or "html". Color values
 * are HTML (swatch + name), so report them as "html" there.
 */
class MiniCartOptionHtmlPlugin
{
    /**
     * @param DefaultItem $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemData(DefaultItem $subject, $result)
    {
        foreach ($result['options'] ?? [] as $i => $option) {
            if (($option['option_type'] ?? null) === OptionColors::OPTION_TYPE) {
                $result['options'][$i]['option_type'] = 'html';
            }
        }
        return $result;
    }
}
