<?php

namespace Drupal\commerce_recurring\Plugin\Condition;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Commerce product variation is recurring' condition.
 *
 * @Condition(
 *   id = "commerce_recurring_commerce_product_variation_is_recurring",
 *   label = @Translation("Commerce product variation is recurring"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "entity" = @ContextDefinition("commerce_product_variation",
 *       label = @Translation("Commerce product variation"),
 *       description = @Translation("Specifies the commerce product variation for which to evaluate the condition.")
 *     )
 *   }
 * )
 */
class CommerceProductVariationIsRecurring extends RulesConditionBase {

  /**
   * Check if the provided commerce product variation is recurring.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The commerce product variation to check.
   *
   * @return bool
   *   TRUE if the provided commerce product variation is recurring.
   */
  protected function doEvaluate(ProductVariationInterface $product_variation) {
    return RecurringController::productVariationIsRecurring($product_variation);
  }

}
