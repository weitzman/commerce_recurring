<?php

namespace Drupal\commerce_recurring\Plugin\Condition;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Order contains a recurring product variation' condition.
 *
 * @Condition(
 *   id = "commerce_recurring_commerce_order_contains_recurring_product_variation",
 *   label = @Translation("Order contains a recurring product variation"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "entity" = @ContextDefinition("commerce_order",
 *       label = @Translation("Order"),
 *       description = @Translation("Specifies the order for which to evaluate the condition.")
 *     )
 *   }
 * )
 */
class CommerceOrderContainsRecurringProductVariation extends RulesConditionBase {

  /**
   * Check if the provided order contains a recurring product variations.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to check.
   *
   * @return bool
   *   TRUE if the provided order is recurring.
   */
  protected function doEvaluate(OrderInterface $order) {
    return RecurringController::orderContainsRecurringProduct($order);
  }

}
