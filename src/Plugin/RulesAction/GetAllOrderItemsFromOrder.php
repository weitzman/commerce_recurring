<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Get all order items that should be copied to a recurring from an order' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_get_all_order_items_to_be_copied_from_order",
 *   label = @Translation("Get all order items that should be copied to a recurring from an order"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the order for which to do the action.")
 *     )
 *   },
 *   provides = {
 *     "recurring_commerce_order_items" = @ContextDefinition(
 *       "list<commerce_order_item>",
 *       label = @Translation("Order Items")
 *     )
 *   }
 * )
 */
class GetAllOrderItemsFromOrder extends RulesActionBase {

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
   * Get all the order items that should be copied to a recurring from an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   */
  protected function doExecute(OrderInterface $order) {
    // @todo Restrict the product variation types to be copied.
    // @todo Add other order items as shipping.
    $recurring_commerce_order_items = RecurringController::getRecurringOrderItemsFromOrder($order);

    $this->setProvidedValue('recurring_commerce_order_items', $recurring_commerce_order_items);
  }

}
