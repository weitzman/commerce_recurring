<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\BillingCycle;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;

/**
 * Defines the interface for subscription types.
 *
 * Subscription types act as subscription bundles, providing additional fields.
 * They also contain billing logic such as calculating charges and manipulating
 * recurring orders.
 */
interface SubscriptionTypeInterface extends BundlePluginInterface {

  /**
   * Gets the subscription type label.
   *
   * @return string
   *   The subscription type label.
   */
  public function getLabel();

  /**
   * Gets the subscription type's purchasable entity type ID.
   *
   * E.g, if subscriptions of this type are used for subscribing to
   * product variations, the ID will be 'commerce_product_variation'.
   *
   * @return string
   *   The purchasable entity type ID, or NULL if the subscription isn't
   *   backed by a purchasable entity.
   */
  public function getPurchasableEntityTypeId();

  /**
   * Collects charges for a subscription's billing cycle.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingCycle $billing_cycle
   *   The billing cycle.
   *
   * @return \Drupal\commerce_recurring\Charge[]
   *   The charges.
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingCycle $billing_cycle);

  /**
   * Creates a recurring order for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The created and saved order.
   */
  public function createRecurringOrder(SubscriptionInterface $subscription);

  /**
   * Renews the recurring order for the given subscription..
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_order\Entity\OrderInterface $previous_recurring_order
   *   The previous recurring order.
   */
  public function renewRecurringOrder(SubscriptionInterface $subscription, OrderInterface $previous_recurring_order);

}
