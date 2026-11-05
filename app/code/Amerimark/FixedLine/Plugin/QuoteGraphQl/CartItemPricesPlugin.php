<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\QuoteGraphQl;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Resolver\CartItemPrices;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Cart item prices: add the per-line fee to original_row_total (original_item_price and price exclude it, so no
 * phantom catalog discount appears) and expose it as fixed_line_fee.
 */
class CartItemPricesPlugin
{
    /**
     * @param TaxConfig $taxConfig
     */
    public function __construct(private readonly TaxConfig $taxConfig)
    {
    }

    /**
     * @param CartItemPrices $subject
     * @param array $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @return array
     */
    public function afterResolve(
        CartItemPrices $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null
    ) {
        $item = $result['model'] ?? null;
        if (!$item || (float)$item->getData(FeeKeys::AMOUNT) <= 0.0) {
            return $result;
        }
        $currency = $item->getQuote()->getQuoteCurrencyCode();
        $inclTax = $this->taxConfig->priceIncludesTax($item->getQuote()->getStore());
        $result['fixed_line_fee'] = ['currency' => $currency, 'value' => (float)$item->getData(FeeKeys::AMOUNT)];
        $result['original_row_total']['value'] += (float)$item->getData(
            $inclTax ? FeeKeys::AMOUNT_INCL_TAX : FeeKeys::AMOUNT
        );
        return $result;
    }
}
