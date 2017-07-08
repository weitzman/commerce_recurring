<?php

namespace Drupal\commerce_recurring\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a main commerce recurring controller.
 */
class RecurringController extends ControllerBase implements RecurringControllerInterface {

  /**
   * The recurring storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $recurringStorage;

  /**
   * Constructs a new RecurringController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->recurringStorage = $entity_type_manager->getStorage('commerce_recurring');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getRecurringOrderItemsFromOrder(OrderInterface $order) {
    $recurring_order_items = [];
    // Get all order order items.
    $order_items = $order->getItems();

    foreach ($order_items as $order_item) {
      // Evaluate every order item.
      if (self::orderItemContainsRecurringProduct($order_item) == TRUE) {
        $recurring_order_items[$order_item->id()] = $order_item;
      }
    }

    return $recurring_order_items;
  }

  /**
   * {@inheritdoc}
   */
  public static function orderItemContainsRecurringProduct(OrderItemInterface $order_item) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $product */
    $product = $order_item->getPurchasedEntity();

    return self::productVariationIsRecurring($product);
  }

  /**
   * {@inheritdoc}
   */
  public static function orderContainsRecurringProduct(OrderInterface $order) {
    // Get all order order items.
    $order_items = $order->getItems();

    foreach ($order_items as $order_item) {
      // Evaluate every order item.
      if (self::orderItemContainsRecurringProduct($order_item) == TRUE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function productVariationIsRecurring(ProductVariationInterface $product) {
    // Evaluate if the product variation type has all required fields.
    return $product->hasField('recurring_initial_price') &&
    $product->hasField('recurring_price') &&
    $product->hasField('recurring_initial_period') &&
    $product->hasField('recurring_end_period') &&
    $product->hasField('recurring_period');
  }

  /**
   * {@inheritdoc}
   */
  public function getDueRecurrings($number_items, $timestamp = NULL) {
    if (empty($timestamp)) {
      /** @var \DateTime $timestamp */
      $timestamp = new DrupalDatetime();
      $timestamp = $timestamp->getTimestamp();
    }

    $result = $this->recurringStorage->getQuery()
      ->condition('status', TRUE)
      ->condition('due_date', $timestamp, '<=')
      ->range(0, $number_items)
      ->execute();

    return $this->recurringStorage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringsOnAnOrder(OrderInterface $order) {
    // Look for all commerce recurring orders that reference to this order.
    // @TODO: This needs work as commerce_recurring_order no longer exists.
    $result = $this->entityQuery->get('commerce_recurring_order')
      ->condition('type', $order->getEntityType())
      ->condition('recurring_orders.target_id', $order->id())
      ->execute();

    return $this->recurringStorage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function orderIsRecurring(OrderInterface $order) {
    // Look for all commerce recurrings that reference to this order.
    // @todo Only enabled recurring orders??
    $result = $this->recurringStorage->getQuery()
      ->condition('type', $order->getEntityType())
      ->condition('recurring_orders.target_id', $order->id())
      ->execute();

    return (count($result) > 0) ? TRUE : FALSE;
  }

}
