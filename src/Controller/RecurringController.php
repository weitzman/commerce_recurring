<?php

namespace Drupal\commerce_recurring\Controller;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a main commerce recurring controller.
 */
class RecurringController extends ControllerBase implements RecurringControllerInterface {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

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
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query) {
    $this->recurringStorage = $entity_type_manager->getStorage('commerce_recurring');
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getRecurringLineItemsFromOrder(OrderInterface $order) {
    $recurring_line_items = [];
    // Get all order line items.
    $line_items = $order->getLineItems();

    foreach ($line_items as $line_item) {
      // Evaluate every line item.
      if (self::lineItemContainsRecurringProduct($line_item) == TRUE) {
        $recurring_line_items[$line_item->id()] = $line_item;
      }
    }

    return $recurring_line_items;
  }

  /**
   * {@inheritdoc}
   */
  public static function lineItemContainsRecurringProduct(LineItemInterface $line_item) {
    /* @var \Drupal\commerce_product\Entity\ProductVariationInterface $product */
    $product = $line_item->getPurchasedEntity();

    return self::productVariationIsRecurring($product);
  }

  /**
   * {@inheritdoc}
   */
  public static function orderContainsRecurringProduct(OrderInterface $order) {
    // Get all order line items.
    $line_items = $order->getLineItems();

    foreach ($line_items as $line_item) {
      // Evaluate every line item.
      if (self::lineItemContainsRecurringProduct($line_item) == TRUE) {
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

    $result = $this->entityQuery->get('commerce_recurring')
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
    $result = $this->entityQuery->get('commerce_recurring')
      ->condition('type', $order->getEntityType())
      ->condition('recurring_orders.target_id', $order->id())
      ->execute();

    return (count($result) > 0) ? TRUE : FALSE;
  }

}
