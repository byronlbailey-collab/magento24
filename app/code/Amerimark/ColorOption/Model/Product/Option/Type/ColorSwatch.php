<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\Product\Option\Type;

use Amerimark\ColorOption\Model\OptionColors;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\StringUtils;

/**
 * "Color" custom option: a single choice among the option's color values. Prices, SKUs and the cart/order machinery
 * are the drop-down's; this class adds the swatch to the HTML shown in the cart, checkout and order views and refuses
 * colors that staff have disabled.
 *
 * The HTML (with the color value at the time) is what order items store, so old orders keep the color they were
 * placed with even if the color is changed later.
 */
class ColorSwatch extends \Magento\Catalog\Model\Product\Option\Type\Select
{
    /**
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StringUtils $string
     * @param Escaper $escaper
     * @param OptionColors $colors
     * @param array $data
     * @param array $singleSelectionTypes
     */
    public function __construct(
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        StringUtils $string,
        Escaper $escaper,
        private readonly OptionColors $colors,
        array $data = [],
        array $singleSelectionTypes = []
    ) {
        parent::__construct($checkoutSession, $scopeConfig, $string, $escaper, $data, $singleSelectionTypes);
    }

    /**
     * The option's value is shown through getFormattedOptionValue() (swatch markup) everywhere, not escaped text.
     *
     * @return bool
     */
    public function isCustomizedView()
    {
        return true;
    }

    /**
     * Only colors that are enabled can be bought.
     *
     * @param array $values
     * @return $this
     * @throws LocalizedException
     */
    public function validateUserValue($values)
    {
        parent::validateUserValue($values);

        $value = $this->getUserValue();
        if (is_scalar($value) && (string)$value !== '') {
            $color = $this->colors->getSwatchData([(int)$value])[(int)$value] ?? null;
            if ($color === null || !$color['is_active']) {
                $this->setIsValid(false);
                throw new LocalizedException(
                    __('Please choose one of the available colors for "%1".', $this->getOption()->getTitle())
                );
            }
        }
        return $this;
    }

    /**
     * Plain color name, for places that cannot show markup (PDFs, text emails, GraphQL).
     *
     * @param string $optionValue
     * @return string
     */
    public function getPrintableOptionValue($optionValue)
    {
        return (string)$this->getEditableOptionValue($optionValue);
    }

    /**
     * Swatch + color name. Span markup with inline styles only (no stylesheet needed, email clients show it). A few
     * views (e.g. the shopper's order history) strip all markup from option values and show just the name.
     *
     * @param string $optionValue
     * @return string
     */
    public function getFormattedOptionValue($optionValue)
    {
        $name = (string)$this->getEditableOptionValue($optionValue);
        if (!is_scalar($optionValue) || !ctype_digit((string)$optionValue)) {
            return $this->_escaper->escapeHtml($name);
        }
        $color = $this->colors->getSwatchData([(int)$optionValue])[(int)$optionValue] ?? null;
        if ($color === null) {
            return $this->_escaper->escapeHtml($name);
        }
        $style = sprintf(
            'display:inline-block;width:18px;height:18px;margin-right:8px;vertical-align:middle;'
            . 'border-radius:50%%;border:1px solid #999;background-color:%s',
            $color['hex']
        );
        return '<span class="amerimark-color-option">'
            . '<span class="amerimark-color-option__dot" style="' . $this->_escaper->escapeHtmlAttr($style) . '"></span>'
            . '<span class="amerimark-color-option__name">' . $this->_escaper->escapeHtml($name) . '</span>'
            . '</span>';
    }
}
