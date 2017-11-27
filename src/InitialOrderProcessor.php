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
   * The order item prorater.
   *
   * @var \Drupal\commerce_recurring\OrderItemProraterInterface
   */
  protected $orderItemProrater;

  /**
   * Constructs a new InitialOrderProcessor object.
   *
   * @param \Drupal\commerce_recurring\OrderItemProraterInterface $order_item_prorater
   *   The order item prorater.
   */
  public function __construct(OrderItemProraterInterface $order_item_prorater) {
    $this->orderItemProrater = $order_item_prorater;
  }

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
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
      $billing_schedule = $purchased_entity->get('billing_schedule')->entity;
      if (!$billing_schedule) {
        continue;
      }

      // Price differences are added as adjustments, to preserve the original
      // price, for both display purposes and for being able to transfer the
      // unit price to the subscription when the order is placed.
      // It's assumed that the customer won't see the actual adjustment,
      // because the cart/order summary was hidden or restyled.
      if ($billing_schedule->getBillingType() == BillingScheduleInterface::BILLING_TYPE_PREPAID) {
        // Prepaid subscriptions need to be prorated so that the customer
        // pays only for the portion of the period that they'll get.
        $unit_price = $order_item->getUnitPrice();
        $prorated_unit_price = $this->orderItemProrater->prorateInitial($order_item, $billing_schedule);
        if (!$prorated_unit_price->equals($unit_price)) {
          $difference = $unit_price->subtract($prorated_unit_price);
          $order_item->addAdjustment(new Adjustment([
            'type' => 'recurring',
            'label' => t('Proration'),
            'amount' => $difference->multiply('-1'),
            'source_id' => $billing_schedule->id(),
          ]));
        }
      }
      else {
        // A postpaid purchased entity is free in the initial order.
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
