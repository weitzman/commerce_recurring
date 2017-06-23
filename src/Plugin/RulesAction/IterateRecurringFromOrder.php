<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Update a recurring entity from a completed order' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_iterate_recurring_from_order",
 *   label = @Translation("Update a recurring entity from a completed order"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Commerce Order"),
 *       description = @Translation("Specifies the commerce order, which should be used.")
 *     ),
 *   }
 * )
 */
class IterateRecurringFromOrder extends RulesActionBase {

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
   * Create/Update a recurring order from line items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order entity.
   */
  protected function doExecute(OrderInterface $commerce_order) {
    // @todo Finish this action.
  }

}
