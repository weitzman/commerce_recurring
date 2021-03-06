<?php

namespace Drupal\Tests\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the product variation subscription type.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\ProductVariation
 * @group commerce_recurring
 */
class ProductVariationTest extends RecurringKernelTestBase {

  /**
   * @covers ::getLabel
   * @covers ::getPurchasableEntityTypeId
   */
  public function testGetters() {
    $plugin_manager = $this->container->get('plugin.manager.commerce_subscription_type');
    /** @var \Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface $plugin */
    $plugin = $plugin_manager->createInstance('product_variation');

    $this->assertEquals('Product variation', $plugin->getLabel());
    $this->assertEquals('commerce_product_variation', $plugin->getPurchasableEntityTypeId());
  }

  /**
   * @covers ::collectCharges
   */
  public function testCharges() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => 'My subscription',
      'quantity' => 2,
      'unit_price' => new Price('49.99', 'USD'),
    ]);
    $subscription->save();
    $start_date = new DrupalDateTime($subscription->getStartTime());
    $billing_cycle = $this->billingSchedule->getPlugin()->generateFirstBillingCycle($start_date);
    $next_billing_cycle = $this->billingSchedule->getPlugin()->generateNextBillingCycle($start_date, $billing_cycle);

    // Postpaid.
    $charges = $subscription->getType()->collectCharges($subscription, $billing_cycle);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals($billing_cycle->getStartDate(), $base_charge->getStartDate());
    $this->assertEquals($billing_cycle->getEndDate(), $base_charge->getEndDate());

    // Prepaid.
    $this->billingSchedule->setBillingType(BillingSchedule::BILLING_TYPE_PREPAID);
    $this->billingSchedule->save();
    $charges = $subscription->getType()->collectCharges($subscription, $billing_cycle);
    $this->assertCount(1, $charges);
    $base_charge = reset($charges);
    $this->assertInstanceOf(Charge::class, $base_charge);
    $this->assertEquals($this->variation, $base_charge->getPurchasedEntity());
    $this->assertEquals($subscription->getTitle(), $base_charge->getTitle());
    $this->assertEquals($subscription->getQuantity(), $base_charge->getQuantity());
    $this->assertEquals($subscription->getUnitPrice(), $base_charge->getUnitPrice());
    $this->assertEquals($next_billing_cycle->getStartDate(), $base_charge->getStartDate());
    $this->assertEquals($next_billing_cycle->getEndDate(), $base_charge->getEndDate());
  }

}
