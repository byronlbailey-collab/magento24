<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\ViewModel;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * What the product page needs to draw a Color option's swatches.
 */
class Swatches implements ArgumentInterface
{
    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
    {
    }

    /**
     * The option's colors in display order. Colors that staff disabled are left out (they cannot be bought), as are
     * values that are not linked to a color.
     *
     * @param Option $option
     * @return array[] each: value (option_type_id), name, hex, source (the core value object)
     */
    public function getSwatches(Option $option): array
    {
        $values = $option->getValues() ?: [];
        $data = $this->colors->getSwatchData(array_map(
            static fn ($value) => (int)$value->getOptionTypeId(),
            $values
        ));
        $swatches = [];
        foreach ($values as $value) {
            $color = $data[(int)$value->getOptionTypeId()] ?? null;
            if ($color === null || !$color['is_active']) {
                continue;
            }
            $swatches[] = [
                'value' => (int)$value->getOptionTypeId(),
                'name' => $color['name'],
                'hex' => $color['hex'],
                'source' => $value,
            ];
        }
        return $swatches;
    }
}
