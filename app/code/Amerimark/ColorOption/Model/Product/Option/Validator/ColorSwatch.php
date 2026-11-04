<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\Product\Option\Validator;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Catalog\Model\Config\Source\Product\Options\Price;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Validator\Select;
use Magento\Catalog\Model\ProductOptions\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;

/**
 * Validates a Color option when a product is saved.
 *
 * On top of the drop-down rules (title, at least one value, valid prices): every row names an existing color, and each
 * color name is used only once in the option (case-insensitive). A group may hold several colors with the same name,
 * and one option may mix colors of several groups.
 */
class ColorSwatch extends Select
{
    /**
     * @param ConfigInterface $productOptionConfig
     * @param Price $priceConfig
     * @param OptionColors $colors
     * @param FormatInterface|null $localeFormat
     */
    public function __construct(
        ConfigInterface $productOptionConfig,
        Price $priceConfig,
        private readonly OptionColors $colors,
        ?FormatInterface $localeFormat = null
    ) {
        parent::__construct($productOptionConfig, $priceConfig, $localeFormat);
    }

    /**
     * @inheritdoc
     */
    public function isValid($option)
    {
        $this->_clearMessages();
        $messages = [];
        if (!$this->validateOptionRequiredFields($option)) {
            $messages['option required fields'] = 'Missed values for option required fields';
        }
        if (!$this->validateOptionType($option)) {
            $messages['option type'] = 'Invalid option type';
        }
        if (!$this->validateOptionValue($option)) {
            $messages['option values'] = 'Add at least one color, with a valid price, to the Color option';
        } else {
            $problem = $this->findColorProblem($option);
            if ($problem !== null) {
                $messages['option colors'] = $problem;
            }
        }
        $this->_addMessages($messages);
        return empty($messages);
    }

    /**
     * @param Option $option
     * @return string|null
     */
    private function findColorProblem(Option $option): ?string
    {
        $values = $option->getValues() ?: $option->getData('values');
        $live = array_filter($values, fn ($value) => empty($value['is_delete']));
        $colors = $this->colors->getColors(array_column($live, 'color_id'));

        $names = [];
        foreach ($live as $value) {
            $color = $colors[(int)($value['color_id'] ?? 0)] ?? null;
            if ($color === null) {
                return 'Choose a color for every row of the Color option';
            }
            $key = mb_strtolower(trim((string)$color['name']));
            if (isset($names[$key])) {
                return sprintf(
                    'The color name "%s" is used more than once in the option "%s". Use each color name once per option.',
                    $color['name'],
                    $option->getTitle()
                );
            }
            $names[$key] = true;
        }
        return null;
    }
}
