<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Quote\Cart;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Quote\Api\Data\TotalsItemExtensionFactory;
use Magento\Quote\Api\Data\TotalsItemInterface;
use Magento\Quote\Model\Cart\Totals\ItemConverter;

/**
 * Exposes the per-line fee as the fixed_line_amount extension attribute of cart totals items (REST).
 */
class TotalsItemConverterPlugin
{
    /**
     * @param TotalsItemExtensionFactory $extensionFactory
     */
    public function __construct(private readonly TotalsItemExtensionFactory $extensionFactory)
    {
    }

    /**
     * @param ItemConverter $subject
     * @param TotalsItemInterface $result
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return TotalsItemInterface
     */
    public function afterModelToDataObject(ItemConverter $subject, $result, $item)
    {
        $fee = $item->getData(FeeKeys::AMOUNT);
        if ($fee !== null && (float)$fee > 0.0) {
            $extension = $result->getExtensionAttributes() ?: $this->extensionFactory->create();
            $extension->setFixedLineAmount((float)$fee);
            $result->setExtensionAttributes($extension);
        }
        return $result;
    }
}
