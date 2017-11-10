<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * Tests the subscription lifecycle.
 */
class SubscriptionLifecycleTest extends RecurringKernelTestBase {

  /**
   * Tests the creation of subscriptions when the order is placed.
   */
  public function testCompleteOrder() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'quantity' => '3',
    ]);
    $order_item->save();

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$order_item],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $order->save();

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    $order->getState()->applyTransition($order->getState()->getTransitions()['place']);
    $order->save();

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethod()->id());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals('active', $subscription->getState()->value);
  }

  public function testActivate() {
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'quantity' => '2',
      'unit_price' => new Price('2', 'USD'),
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
    $this->assertEquals($this->user->id(), $order->getCustomer()->id());
    $this->assertEquals('recurring', $order->bundle());
    $order_item = $order->getItems()[0];
    $this->assertEquals('recurring_product_variation', $order_item->bundle());
    $this->assertEquals('2', $order_item->getQuantity());
    $this->assertEquals('4.00', $order_item->getTotalPrice()->getNumber());
    $this->assertEquals('commerce_product_variation', $order_item->getPurchasedEntity()->getEntityTypeId());
    $this->assertEquals($this->variation->id(), $order_item->getPurchasedEntity()->id());
    $this->assertEquals($subscription->get('starts')->value, $billing_cycle->getStartDate()->format('U'));
    $this->assertEquals($subscription->get('starts')->value + 50, $billing_cycle->getEndDate()->format('U'));
    $this->assertEquals($subscription->get('starts')->value, $order_item->get('starts')->value);
    $this->assertEquals($subscription->get('starts')->value + 50, $order_item->get('ends')->value);
  }

}
