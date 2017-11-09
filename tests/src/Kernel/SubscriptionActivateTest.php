<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;

class SubscriptionActivateTest extends CommerceRecurringKernelTestBase {

  public function testActivate() {
    $currentUser = $this->createUser([], []);
    \Drupal::currentUser()->setAccount($currentUser);

    $subscription = Subscription::create([
      'type' => 'license',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $currentUser,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'amount' => new Price('2', 'USD'),
      'state' => 'pending',
      'starts' => \Drupal::time()->getRequestTime() - 5,
      'ends' => \Drupal::time()->getRequestTime() + 1000,
    ]);
    $subscription->save();

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertEmpty($result);

    $subscription->getState()->applyTransition($subscription->getState()->getTransitions()['activate']);
    $subscription->save();

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertNotEmpty($result);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load(reset($result));
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_item */
    $billing_cycle_item = $order->get('billing_cycle')->first();
    $billing_cycle = $billing_cycle_item->toBillingCycle();

    $this->assertEquals($subscription->getStoreId(), $order->getStoreId());
    $this->assertEquals($currentUser->id(), $order->getCustomer()->id());
    $this->assertEquals('recurring', $order->bundle());
    $order_item = $order->getItems()[0];
    $this->assertEquals('recurring', $order_item->bundle());
    $this->assertEquals(2, $order_item->getTotalPrice()->getNumber());
    $this->assertEquals('commerce_subscription', $order_item->getPurchasedEntity()->getEntityTypeId());
    $this->assertEquals($subscription->id(), $order_item->getPurchasedEntity()->id());
    $this->assertEquals($subscription->get('starts')->value, $billing_cycle->getStartDate()->format('U'));
    $this->assertEquals($subscription->get('starts')->value + 50, $billing_cycle->getEndDate()->format('U'));
    $this->assertEquals($subscription->get('starts')->value, $order_item->get('started')->value);
    $this->assertEquals($subscription->get('starts')->value + 50, $order_item->get('ended')->value);
  }

}
