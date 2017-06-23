<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Get all the line items containing recurring products from an order' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_get_recurring_line_items_from_order",
 *   label = @Translation("Get all the line items containing recurring products from an order"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Commerce Order"),
 *       description = @Translation("Specifies the commerce order for which to do the action.")
 *     )
 *   },
 *   provides = {
 *     "recurring_commerce_line_items" = @ContextDefinition(
 *       "list<commerce_line_item>",
 *       label = @Translation("Line Items")
 *     )
 *   }
 * )
 */
class GetRecurringLineItemsFromOrder extends RulesActionBase {

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
   * Get all the line items containing recurring products from a commerce order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order entity.
   */
  protected function doExecute(OrderInterface $commerce_order) {
    $recurring_commerce_line_items = RecurringController::getRecurringLineItemsFromOrder($commerce_order);

    $this->setProvidedValue('recurring_commerce_line_items', $recurring_commerce_line_items);
  }

}
