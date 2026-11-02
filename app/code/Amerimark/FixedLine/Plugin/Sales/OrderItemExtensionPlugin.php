<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Plugin\Sales;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Exposes the per-line fee as the fixed_line_amount extension attribute of order items (REST).
 * Declared on both the order repository and the order item repository.
 */
class OrderItemExtensionPlugin
{
    /**
     * @param OrderItemExtensionFactory $extensionFactory
     */
    public function __construct(private readonly OrderItemExtensionFactory $extensionFactory)
    {
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     */
    public function afterGet($subject, $result)
    {
        if ($result instanceof OrderInterface) {
            foreach ((array)$result->getItems() as $item) {
                $this->apply($item);
            }
        } elseif ($result instanceof OrderItemInterface) {
            $this->apply($result);
        }
        return $result;
    }

    /**
     * @param OrderRepositoryInterface|OrderItemRepositoryInterface $subject
     * @param OrderSearchResultInterface|\Magento\Sales\Api\Data\OrderItemSearchResultInterface $result
     * @return mixed
     */
    public function afterGetList($subject, $result)
    {
        foreach ($result->getItems() as $entity) {
            if ($entity instanceof OrderInterface) {
                foreach ((array)$entity->getItems() as $item) {
                    $this->apply($item);
                }
            } elseif ($entity instanceof OrderItemInterface) {
                $this->apply($entity);
            }
        }
        return $result;
    }

    /**
     * @param OrderItemInterface $item
     * @return void
     */
    private function apply(OrderItemInterface $item): void
    {
        $fee = $item->getData(FeeKeys::AMOUNT);
        if ($fee !== null && (float)$fee > 0.0) {
            $extension = $item->getExtensionAttributes() ?: $this->extensionFactory->create();
            $extension->setFixedLineAmount((float)$fee);
            $item->setExtensionAttributes($extension);
        }
    }
}
