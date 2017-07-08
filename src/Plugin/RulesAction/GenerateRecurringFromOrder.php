<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Create/Update a recurring order from order items' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_generate_recurring_order",
 *   label = @Translation("Create/Update a recurring order from order items"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the recurring order, which should be created/updated.")
 *     ),
 *     "commerce_order_items" = @ContextDefinition("list<commerce_order_item>",
 *       label = @Translation("Order items"),
 *     )
 *   }
 * )
 */
class GenerateRecurringFromOrder extends RulesActionBase {

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
   * Create/Update a recurring order from order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   * @param array $order_items
   *   The order items entity.
   */
  protected function doExecute(OrderInterface $order, array $order_items = []) {
    // @todo Finish this action.
  }

}
