<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Cron
 * @group commerce_recurring
 */
class CronTest extends RecurringKernelTestBase {

  /**
   * @covers ::run
   */
  public function testRun() {
    // Ensure that the customer has an email (for dunning emails).
    $this->user->setEmail($this->randomMachineName() . '@example.com');

    $first_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => \Drupal::time()->getRequestTime(),
      'ends' => 0,
    ]);
    $first_subscription->save();

    $second_subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => \Drupal::time()->getRequestTime() + 200,
      'ends' => 0,
    ]);
    $second_subscription->save();

    // Rewind time to the end of the first subscription.
    // Confirm that only the first subscription's order was queued.
    $this->rewindTime($first_subscription->get('starts')->value + 100);
    $this->container->get('commerce_recurring.cron')->run();

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');
    $counts = array_filter($queue->getBackend()->countJobs());
    $this->assertEquals([Job::STATE_QUEUED => 2], $counts);

    $job1 = $queue->getBackend()->claimJob();
    $job2 = $queue->getBackend()->claimJob();
    $this->assertArraySubset(['order_id' => '1'], $job1->getPayload());
    $this->assertEquals('commerce_recurring_order_close', $job1->getType());
    $this->assertArraySubset(['order_id' => '1'], $job2->getPayload());
    $this->assertEquals('commerce_recurring_order_renew', $job2->getType());

    /** @var \Drupal\advancedqueue\ProcessorInterface $processor */
    $processor = \Drupal::service('advancedqueue.processor');
    $result = $processor->processJob($job1, $queue);
  }

}
