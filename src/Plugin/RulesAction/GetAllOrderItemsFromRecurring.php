<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Entity\RecurringInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Get all order items that should be copied to a recurring order from a recurring' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_get_all_order_items_to_be_copied_from_recurring",
 *   label = @Translation("Get all order items that should be copied to a recurring order from a recurring"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_recurring" = @ContextDefinition(
 *       "commerce_recurring",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the recurring for which to do the action.")
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
class GetAllOrderItemsFromRecurringOrder extends RulesActionBase {

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
   * Get all the order items hat should be copied to a recurring order from a recurring.
   *
   * @param \Drupal\commerce_recurring\Entity\RecurringInterface $commerce_recurring
   *   The commerce recurring entity.
   */
  protected function doExecute(RecurringInterface $commerce_recurring) {
    $this->setProvidedValue('recurring_commerce_order_items', $commerce_recurring->getOrderItems());
  }

}
