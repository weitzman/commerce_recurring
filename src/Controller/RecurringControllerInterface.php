<?php

namespace Drupal\commerce_recurring\Controller;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Defines the interface for commerce recurring controller.
 */
interface RecurringControllerInterface {

  /**
   * Gets all recurring line items from a commerce order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order entity.
   *
   * @return array
   *   An array with the recurring line items.
   */
  public static function getRecurringLineItemsFromOrder(OrderInterface $order);

  /**
   * Determines if the current line item contains a recurring product.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The commerce line item entity.
   *
   * @return bool
   *   TRUE if contains a recurring product, otherwise FALSE.
   */
  public static function lineItemContainsRecurringProduct(LineItemInterface $line_item);

  /**
   * Determines if the current order contains a recurring product.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order entity.
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
   * Get all the recurrings on an commerce order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order entity.
   *
   * @return array
   *   An array of loaded commerce recurrings.
   */
  public function getRecurringsOnAnOrder(OrderInterface $order);

  /**
   * Determines if the current commerce order is a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order entity.
   *
   * @return bool
   *   TRUE if there are any recurring referencing to this order, otherwise
   *   FALSE.
   */
  public function orderIsRecurring(OrderInterface $order);

}
