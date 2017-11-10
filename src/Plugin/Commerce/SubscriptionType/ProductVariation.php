<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Provides the product variation subscription type.
 *
 * @CommerceSubscriptionType(
 *   id = "product_variation",
 *   label = @translation("Product variation"),
 *   purchasable_entity_type = "commerce_product_variation",
 * )
 */
class ProductVariation extends SubscriptionTypeBase {

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingCycle $billing_cycle) {
    return [];
  }

}
