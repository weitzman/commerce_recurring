<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Provides the standalone subscription type (not backed by a purchased entity).
 *
 * @CommerceSubscriptionType(
 *   id = "standalone",
 *   label = @translation("standalone"),
 * )
 */
class Standalone extends SubscriptionTypeBase {

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingCycle $billing_cycle) {
    $base_charge = new Charge($subscription->getUnitPrice(), $subscription->getTitle(), $billing_cycle->getStartDate(), $billing_cycle->getEndDate());
    return [$base_charge];
  }

}
