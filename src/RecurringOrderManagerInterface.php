<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Manages recurring orders.
 */
interface RecurringOrderManagerInterface {

  /**
   * Ensures a recurring order for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The recurring order.
   */
  public function ensureOrder(SubscriptionInterface $subscription);

  /**
   * Refreshes the given recurring order.
   *
   * Each subscription's order items will be rebuilt based on the most
   * recent chargers.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   */
  public function refreshOrder(OrderInterface $order);

  /**
   * Renews the given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The next recurring order.
   */
  public function renewOrder(OrderInterface $order);

}
