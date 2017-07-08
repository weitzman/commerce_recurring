<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Create/Update a recurring entity from product data' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_generate_recurring_product",
 *   label = @Translation("Create/Update a recurring order from product data"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the recurring order, which should be created/updated.")
 *     ),
 *     "commerce_order_item" = @ContextDefinition("commerce_order_item",
 *       label = @Translation("Order Item"),
 *     ),
 *     "fixed_price" = @ContextDefinition("commerce_price",
 *       label = @Translation("Fixed price for the recurring entity"),
 *     ),
 *     "quantity" = @ContextDefinition("decimal",
 *       label = @Translation("Quantity"),
 *     )
 *   }
 * )
 */
class GenerateRecurringFromProduct extends RulesActionBase {

  /**
   * The main commerce recurring controller.
   *
   * @var \Drupal\commerce_recurring\Controller\RecurringController
   */
  protected $recurringController;

  /**
   * {@inheritdoc}
   */
  public function __construct(RecurringController $recurring_controller, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->recurringController = $recurring_controller;
  }

  /**
   * Generate the order associated to the order recurring entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item entity.
   * @param \Drupal\commerce_price\Price $fixed_price
   *   The fixed price object for the recurring entity.
   * @param int $quantity
   *   The quantity for the order item units.
   */
  protected function doExecute(OrderInterface $order, OrderItemInterface $order_item, Price $fixed_price, $quantity) {
    // @todo Finish this action.
  }

}
