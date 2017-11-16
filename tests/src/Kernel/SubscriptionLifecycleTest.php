<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * Tests the subscription lifeperiod.
 */
class SubscriptionLifeperiodTest extends RecurringKernelTestBase {

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

    $order->state = 'completed';
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

    // Confirm that a recurring order is present.
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertNotEmpty($result);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load(reset($result));
    $this->assertNotEmpty($order);
  }

}
