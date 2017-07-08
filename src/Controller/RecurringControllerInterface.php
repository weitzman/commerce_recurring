<?php

namespace Drupal\commerce_recurring\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Defines the interface for commerce recurring controller.
 */
interface RecurringControllerInterface {

  /**
   * Gets all recurring order items from an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return array
   *   An array with the recurring order items.
   */
  public static function getRecurringOrderItemsFromOrder(OrderInterface $order);

  /**
   * Determines if the current order item contains a recurring product.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   *
   * @return bool
   *   TRUE if contains a recurring product, otherwise FALSE.
   */
  public static function orderItemContainsRecurringProduct(OrderItemInterface $order_item);

  /**
   * Determines if the current order contains a recurring product.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return bool
   *   TRUE if there are any recurring product, otherwise FALSE.
   */
  public static function orderContainsRecurringProduct(OrderInterface $order);

  /**
   * Determines if the current commerce product variation is recurring.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product
   *   The commerce product variation entity.
   *
   * @return bool
   *   TRUE if the commerce product variation has all required fields.
   */
  public static function productVariationIsRecurring(ProductVariationInterface $product);

  /**
   * Get the recurrings about to due.
   *
   * @param int $number_items
   *    The number of items to retrieve.
   * @param int $timestamp
   *    The due date.
   *
   * @return array
   *   An array of loaded commerce recurrings.
   */
  public function getDueRecurrings($number_items, $timestamp = NULL);

  /**
   * Get all the recurrings on an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return array
   *   An array of loaded commerce recurrings.
   */
  public function getRecurringsOnAnOrder(OrderInterface $order);

  /**
   * Determines if the current order is a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return bool
   *   TRUE if there are any recurring referencing to this order, otherwise
   *   FALSE.
   */
  public function orderIsRecurring(OrderInterface $order);

}
