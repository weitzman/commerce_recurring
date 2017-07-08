<?php

namespace Drupal\commerce_recurring\Plugin\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Order is recurring' condition.
 *
 * @Condition(
 *   id = "commerce_recurring_commerce_order_is_recurring",
 *   label = @Translation("Order is recurring"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "entity" = @ContextDefinition("commerce_order",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the order for which to evaluate the condition.")
 *     )
 *   }
 * )
 */
class CommerceOrderIsRecurring extends RulesConditionBase {

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
   * Check if the provided order is recurring.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to check.
   *
   * @return bool
   *   TRUE if the provided order is recurring.
   */
  protected function doEvaluate(OrderInterface $order) {
    return $this->recurringController->orderIsRecurring($order);
  }

}
