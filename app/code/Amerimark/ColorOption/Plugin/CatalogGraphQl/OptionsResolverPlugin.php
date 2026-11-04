<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\CatalogGraphQl;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\CatalogGraphQl\Model\Resolver\Product\Options;

/**
 * Gives the colors of a Color option their display color, and leaves out colors staff have disabled (they cannot be
 * bought). The title of each value is the color's name.
 */
class OptionsResolverPlugin
{
    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
    {
    }

    /**
     * @param Options $subject
     * @param array|null $result
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param mixed $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @return array|null
     */
    public function afterResolve(Options $subject, $result, $field, $context, $info, ?array $value = null)
    {
        if (!is_array($result)) {
            return $result;
        }
        foreach ($result as $key => $entry) {
            if (($entry['type'] ?? null) !== OptionColors::OPTION_TYPE || !is_array($entry['value'] ?? null)) {
                continue;
            }
            $swatches = $this->colors->getSwatchData(array_map(
                static fn ($row) => (int)($row['option_type_id'] ?? 0),
                $entry['value']
            ));
            $values = [];
            foreach ($entry['value'] as $row) {
                $color = $swatches[(int)($row['option_type_id'] ?? 0)] ?? null;
                if ($color === null || !$color['is_active']) {
                    continue;
                }
                $row['hex'] = $color['hex'];
                $values[] = $row;
            }
            $result[$key]['value'] = $values;
        }
        return $result;
    }
}
