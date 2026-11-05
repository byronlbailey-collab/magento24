<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Catalog\Block;

use Amerimark\FixedLine\Model\PriceType;
use Magento\Catalog\Block\Product\View\Options;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Keeps FixedLine options out of the per-unit price box on the product page.
 *
 * The widget (price-options.js) adds each selected option's prices to the unit price. For FixedLine options the
 * amounts are zeroed, and drop-down/radio/checkbox/multi-select value names get a "(+$50.00 per line)" suffix
 * (the widget renders option text from the name when the price is zero).
 */
class OptionsJsonConfigPlugin
{
    /**
     * @param Json $json
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly Json $json,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * @param Options $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Options $subject, $result)
    {
        $config = $this->json->unserialize((string)$result);
        if (!is_array($config)) {
            return $result;
        }
        $changed = false;
        foreach ($subject->getOptions() as $option) {
            $optionId = $option->getId();
            if (!isset($config[$optionId])) {
                continue;
            }
            if ($option->hasValues()) {
                foreach ($option->getValues() as $valueId => $value) {
                    if (PriceType::isFixedLine($value->getPriceType()) && isset($config[$optionId][$valueId])) {
                        $config[$optionId][$valueId] = $this->neutralize(
                            $config[$optionId][$valueId],
                            (float)$value->getData('price'),
                            true
                        );
                        $changed = true;
                    }
                }
            } elseif (PriceType::isFixedLine($option->getPriceType())) {
                $config[$optionId] = $this->neutralize($config[$optionId], (float)$option->getData('price'), false);
                $changed = true;
            }
        }
        return $changed ? $this->json->serialize($config) : $result;
    }

    /**
     * @param array $entry
     * @param float $fee
     * @param bool $rename
     * @return array
     */
    private function neutralize(array $entry, float $fee, bool $rename): array
    {
        foreach (['oldPrice', 'basePrice', 'finalPrice'] as $key) {
            if (isset($entry['prices'][$key])) {
                $entry['prices'][$key]['amount'] = 0;
                $entry['prices'][$key]['adjustments'] = [];
            }
        }
        if ($rename && isset($entry['name'])) {
            $entry['name'] .= ' (+' . $this->priceCurrency->convertAndFormat($fee, false) . ' ' . __('per line') . ')';
        }
        return $entry;
    }
}
