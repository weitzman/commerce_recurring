<?php

namespace Drupal\commerce_recurring\Plugin\Condition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Order item contains a recurring product variation' condition.
 *
 * @Condition(
 *   id = "commerce_recurring_commerce_order_item_contains_recurring_product_variation",
 *   label = @Translation("Order item contains a recurring product variation"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "entity" = @ContextDefinition("commerce_order_item",
 *       label = @Translation("Order item"),
 *       description = @Translation("Specifies the order item for which to evaluate the condition.")
 *     )
 *   }
 * )
 */
class CommerceOrderItemContainsRecurringProductVariation extends RulesConditionBase {

  /**
   * Check if the provided order item contains a recurring product variations.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order to check.
   *
   * @return bool
   *   TRUE if the provided order is recurring.
   */
  protected function doEvaluate(OrderItemInterface $order_item) {
    return RecurringController::orderItemContainsRecurringProduct($order_item);
  }

}
