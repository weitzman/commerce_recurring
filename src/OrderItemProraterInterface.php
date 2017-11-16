<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Prorates order items.
 *
 * Prorating is the process of adjusting unit prices to reflect the billing
 * period. For example, if the order's billing period is Jun 1st - Jul 1st,
 * but the order item's billing period is Jun 1st - Jun 16th (because a plan
 * change or a cancellation happened then), the order item's unit price should
 * only be half of the usual price.
 */
interface OrderItemProraterInterface {

  /**
   * Prorates the given initial order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule
   *   The billing schedule.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date/time. Defaults to the current date/time.
   *
   * @return \Drupal\commerce_price\Price
   *   The prorated unit price.
   */
  public function prorateInitial(OrderItemInterface $order_item, BillingScheduleInterface $billing_schedule, DrupalDateTime $start_date = NULL);

  /**
   * Prorates the given recurring order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The order billing period.
   */
  public function prorateRecurring(OrderItemInterface $order_item, BillingPeriod $billing_period);

}
