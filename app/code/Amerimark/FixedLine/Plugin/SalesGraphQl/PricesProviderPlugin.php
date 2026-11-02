<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\SalesGraphQl;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Sales\Model\Order\Item;
use Magento\SalesGraphQl\Model\OrderItemPrices\PricesProvider;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Order item prices: include the per-line fee in original_row_total(+incl. tax) and expose fixed_line_fee.
 */
class PricesProviderPlugin
{
    /**
     * @param TaxConfig $taxConfig
     */
    public function __construct(private readonly TaxConfig $taxConfig)
    {
    }

    /**
     * @param PricesProvider $subject
     * @param array $result
     * @param Item $orderItem
     * @return array
     */
    public function afterExecute(PricesProvider $subject, $result, Item $orderItem)
    {
        $fee = (float)$orderItem->getData(FeeKeys::AMOUNT);
        if ($fee <= 0.0) {
            return $result;
        }
        $feeIncl = (float)$orderItem->getData(FeeKeys::AMOUNT_INCL_TAX);
        $inclTax = $this->taxConfig->priceIncludesTax($orderItem->getOrder()->getStore());
        $result['fixed_line_fee'] = [
            'currency' => $orderItem->getOrder()->getOrderCurrencyCode(),
            'value' => $fee,
        ];
        $result['original_row_total']['value'] += $inclTax ? $feeIncl : $fee;
        $result['original_row_total_including_tax']['value'] += $feeIncl;
        return $result;
    }
}
