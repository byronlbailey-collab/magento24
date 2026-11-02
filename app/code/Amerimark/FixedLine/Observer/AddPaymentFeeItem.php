<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Observer;

use Amerimark\FixedLine\Model\FeeKeys;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Cart;

/**
 * Keeps gateway line items summing to the order total: generic gateways build lines as unit price * qty,
 * which excludes the once-per-line fee. Adds the fees as a custom item and removes them from the
 * imported subtotal (which already includes them) so subtotal still equals the sum of line items.
 *
 * PayPal's cart already collapses lines whose price * qty differs from the row total.
 */
class AddPaymentFeeItem implements ObserverInterface
{
    private const PAYPAL_CART = 'Magento\\Paypal\\Model\\Cart';

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /** @var Cart $cart */
        $cart = $observer->getEvent()->getData('cart');
        if (!$cart || is_a($cart, self::PAYPAL_CART)) {
            return;
        }
        $total = 0.0;
        foreach ($cart->getSalesModel()->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $original = $item->getOriginalItem();
            $total += $original ? (float)$original->getData(FeeKeys::BASE_AMOUNT) : 0.0;
        }
        if ($total > 0.0) {
            $cart->addSubtotal(-$total);
            $cart->addCustomItem(__('Per-line charges')->render(), 1, $total);
        }
    }
}
