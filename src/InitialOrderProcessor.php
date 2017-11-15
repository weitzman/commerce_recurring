<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;

/**
 * Modifies the price of order items which start subscriptions.
 */
class InitialOrderProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order->bundle() == 'recurring') {
      return;
    }

    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || !$purchased_entity->hasField('billing_schedule')) {
        return;
      }
      /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
      $billing_schedule = $purchased_entity->get('billing_schedule')->entity;
      if ($billing_schedule && $billing_schedule->getBillingType() == BillingScheduleInterface::BILLING_TYPE_POSTPAID) {
        // A postpaid purchased entity is free in the initial order.
        // The price difference is added as an adjustment, to preserve the
        // original price, for both display purposes and for being able to
        // transfer the unit price to the subscription when the order is placed.
        // It's assumed that the customer won't see the actual adjustment,
        // because the cart/order summary was hidden or restyled.
        $order_item->addAdjustment(new Adjustment([
          'type' => 'recurring',
          'label' => t('Pay later'),
          'amount' => $order_item->getAdjustedUnitPrice()->multiply('-1'),
          'source_id' => $billing_schedule->id(),
        ]));
      }
    }
  }

}
