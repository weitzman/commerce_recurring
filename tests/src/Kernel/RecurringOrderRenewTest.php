<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_recurring\RecurringCron;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Tests the logic to determine which orders should be refreshed.
 *
 * @group commerce_recurring
 */
class RecurringOrderRenewTest extends RecurringKernelTestBase {
  
  protected function setUp() {
    parent::setUp();

    \Drupal::getContainer()->set('datetime.time', new CustomTime(\Drupal::time()->getRequestTime()));
  }

  protected function createBasicSubscriptionAndOrder() {
    // Create a recurring order by creating a subscription.
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
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

    $orders = $order_storage->loadMultiple($order_storage->getQuery()
      ->condition('type', 'recurring')
      ->execute());
    $this->assertCount(1, $orders);
    $order = reset($orders);

    return [$subscription, $order];
  }

  /**
   * Tests the logic to fill up the recurring order queue for refresh and close.
   */
  public function testRecurringOrderRefreshQueue() {
    list($subscription, $order) = $this->createBasicSubscriptionAndOrder();

    // Ensure the refresh queue is empty.
    $this->assertEquals(0, \Drupal::queue('commerce_recurring_refresh')->numberOfItems());

    // Fast forward in time and run cron.
    
    \Drupal::time()->setTime($subscription->get('starts')->value + 100);
    // We don't trigger the cron directly as this processes the queue items
    // already.
    RecurringCron::create(\Drupal::getContainer())->cron();

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $this->assertEquals(['queued' => 2, 'processing' => 0,'success' => 0, 'failure' => 0], $queue->getBackend()->countJobs());

    $job1 = $queue->getBackend()->claimJob();
    $job2 = $queue->getBackend()->claimJob();

    $this->assertArraySubset(['order_id' => $order->id()], $job1->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $job1->getType());
    $this->assertArraySubset(['order_id' => $order->id()], $job2->getPayload());
    $this->assertEquals('commerce_recurring_order_renew', $job2->getType());
  }

  /**
   * Tests the actual logic of recurring a recurring order.
   */
  public function testRecurringOrderRefreshLogic() {
    list($subscription, $order) = $this->createBasicSubscriptionAndOrder();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $next_order */
    $next_order = $subscription->getType()->renewRecurringOrder($subscription, $order);
    $this->assertNotEquals($next_order->id(), $order->id());
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_item */
    $billing_cycle_item = $next_order->get('billing_cycle')->first();
    $billing_cycle = $billing_cycle_item->toBillingCycle();

    $this->assertEquals('recurring', $next_order->bundle());
    $this->assertEquals(\Drupal::time()->getRequestTime() + 45, $billing_cycle->getStartDate()->format('U'));
    $this->assertEquals(\Drupal::time()->getRequestTime() + 95, $billing_cycle->getEndDate()->format('U'));
    $this->assertCount(1, $next_order->getItems());
    $this->assertEquals(2, $next_order->getItems()[0]->getUnitPrice()->getNumber());
    $this->assertEquals('recurring_product_variation', $next_order->getItems()[0]->bundle());
    $this->assertEquals(1, $next_order->getItems()[0]->getQuantity());
  }

}

class CustomTime implements TimeInterface {

  /**
   * @var int
   */
  protected $time;

  /**
   * CustomTime constructor.
   * @param int $time
   */
  public function __construct($time) {
    $this->time = $time;
  }

  /**
   * @param int $time
   */
  public function setTime($time) {
    $this->time = $time;
  }

  public function getRequestTime() {
    return $this->time;
  }

  public function getRequestMicroTime() {
    return $this->time;
  }

  public function getCurrentTime() {
    return $this->time;
  }

  public function getCurrentMicroTime() {
    return $this->time;
  }

}
