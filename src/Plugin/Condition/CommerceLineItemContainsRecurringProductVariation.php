<?php

namespace Drupal\commerce_recurring\Plugin\Condition;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Commerce line item contains a recurring product variation' condition.
 *
 * @Condition(
 *   id = "commerce_recurring_commerce_line_item_contains_recurring_product_variation",
 *   label = @Translation("Commerce line item contains a recurring product variation"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "entity" = @ContextDefinition("commerce_line_item",
 *       label = @Translation("Commerce line item"),
 *       description = @Translation("Specifies the commerce line item for which to evaluate the condition.")
 *     )
 *   }
 * )
 */
class CommerceLineItemContainsRecurringProductVariation extends RulesConditionBase {

  /**
   * Check if the provided commerce line item contains a recurring product variations.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The commerce order to check.
   *
   * @return bool
   *   TRUE if the provided commerce order is recurring.
   */
  protected function doEvaluate(LineItemInterface $line_item) {
    return RecurringController::lineItemContainsRecurringProduct($line_item);
  }

}
