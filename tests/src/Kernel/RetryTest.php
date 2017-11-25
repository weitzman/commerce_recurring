<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\RecurringOrderClose
 * @group commerce_recurring
 */
class RetryTest extends RecurringKernelTestBase {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * The used queue.
   *
   * @var \Drupal\advancedqueue\Entity\QueueInterface
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $queue_storage */
    $queue_storage = $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue');
    $this->queue = $queue_storage->load('commerce_recurring');
  }

  /**
   * @covers ::process
   * @covers ::handleDecline
   * @covers ::updateSubscriptions
   */
  public function testRetry() {
    // A subscription without a payment method, to ensure a decline.
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2017-02-24 17:00'),
    ]);
    $subscription->save();
    $order = $this->recurringOrderManager->ensureOrder($subscription);

    // Rewind time to the end of the first subscription.
    $this->rewindTime(strtotime('2017-02-24 19:00'));
    $job = Job::create('commerce_recurring_order_close', [
      'order_id' => $order->id(),
    ]);
    $this->queue->enqueueJob($job);

    $job = $this->queue->getBackend()->claimJob();
    /** @var \Drupal\advancedqueue\ProcessorInterface $processor */
    $processor = \Drupal::service('advancedqueue.processor');
    $result = $processor->processJob($job, $this->queue);

    // Confirm that the order was placed.
    $order = $this->reloadEntity($order);
    $this->assertEquals('needs_payment', $order->getState()->value);
    // Confirm that the job result is correct.
    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
    $this->assertEquals('Payment method not found.', $result->getMessage());
    $this->assertEquals(3, $result->getMaxRetries());
    $this->assertEquals(86400, $result->getRetryDelay());
    // Confirm that the job was re-queued.
    $this->assertEquals(1, $job->getNumRetries());
    $this->assertEquals(Job::STATE_QUEUED, $job->getState());
    $this->assertEquals(strtotime('2017-02-25 19:00'), $job->getAvailableTime());

    // Run the first retry.
    $this->rewindTime(strtotime('2017-02-25 19:00'));
    $job = $this->queue->getBackend()->claimJob();
    $result = $processor->processJob($job, $this->queue);

    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
    $this->assertEquals('Payment method not found.', $result->getMessage());
    $this->assertEquals(3, $result->getMaxRetries());
    $this->assertEquals(86400 * 3, $result->getRetryDelay());
    // Confirm that the job was re-queued.
    $this->assertEquals(2, $job->getNumRetries());
    $this->assertEquals(Job::STATE_QUEUED, $job->getState());
    $this->assertEquals(strtotime('2017-02-28 19:00'), $job->getAvailableTime());

    // Run the second retry.
    $this->rewindTime(strtotime('2017-02-28 19:00'));
    $job = $this->queue->getBackend()->claimJob();
    $result = $processor->processJob($job, $this->queue);

    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
    $this->assertEquals('Payment method not found.', $result->getMessage());
    $this->assertEquals(3, $result->getMaxRetries());
    $this->assertEquals(86400 * 5, $result->getRetryDelay());
    // Confirm that the job was re-queued.
    $this->assertEquals(3, $job->getNumRetries());
    $this->assertEquals(Job::STATE_QUEUED, $job->getState());
    $this->assertEquals(strtotime('2017-03-05 19:00'), $job->getAvailableTime());

    // Run the last retry.
    $this->rewindTime(strtotime('2017-03-05 19:00'));
    $job = $this->queue->getBackend()->claimJob();
    $result = $processor->processJob($job, $this->queue);

    // Confirm that the order was marked as failed.
    $order = $this->reloadEntity($order);
    $this->assertEquals('failed', $order->getState()->value);
    // Confirm that the job result is correct.
    $this->assertEquals(Job::STATE_SUCCESS, $result->getState());
    $this->assertEquals('Dunning complete, recurring order not paid.', $result->getMessage());
    // Confirm that the job was not requeued.
    $this->assertEquals(3, $job->getNumRetries());
    $this->assertEquals(Job::STATE_SUCCESS, $job->getState());
    // Confirm that the subscription was canceled.
    $subscription = $this->reloadEntity($subscription);
    $this->assertEquals('canceled', $subscription->getState()->value);
  }

  /**
   * {@inheritdoc}
   */
  protected function rewindTime($new_time) {
    parent::rewindTime($new_time);

    // Reload the queues so that their backends get the updated service.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $queue_storage */
    $queue_storage = $this->container->get('entity_type.manager')->getStorage('advancedqueue_queue');
    $queue_storage->resetCache(['commerce_recurring']);
    $this->queue = $queue_storage->load('commerce_recurring');
  }

}
