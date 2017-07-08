<?php

namespace Drupal\commerce_recurring\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Entity\RecurringInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a recurring order is paid in full.
 *
 * @see
 */
class RecurringOrderPaidFullEvent extends Event {

  const EVENT_NAME = 'commerce_recurring_paid_full';

  /**
   * The recurring order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  public $order;

  /**
   * The commerce recurring.
   *
   * @var \Drupal\commerce_recurring\Entity\RecurringInterface
   */
  public $recurring;

  /**
   * The number of orders.
   *
   * @var int
   */
  public $numberOfOrders;

  /**
   * Constructs a new RecurringOrderPaidFull event.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_recurring\Entity\RecurringInterface $commerce_recurring
   *   The commerce recurring.
   * @param int $number_of_orders
   *   The number of orders.
   */
  public function __construct(OrderInterface $order, RecurringInterface $commerce_recurring, $number_of_orders = 0) {
    $this->order = $order;
    $this->recurring = $commerce_recurring;
    $this->numberOfOrders = $number_of_orders;
  }

  /**
   * Gets the recurring order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   Gets the recurring order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the commerce recurring order.
   *
   * @return \Drupal\commerce_recurring\Entity\RecurringInterface
   *   Gets the commerce recurring order.
   */
  public function getRecurring() {
    return $this->recurring;
  }

  /**
   * Gets the number of orders.
   *
   * @return int
   *   Gets the number of orders.
   */
  public function getNumberOfOrders() {
    return $this->numberOfOrders;
  }

}
