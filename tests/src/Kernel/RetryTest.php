<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\RecurringOrderClose
 * @group commerce_recurring
 */
class RetryTest extends RecurringKernelTestBase {

  use AssertMailTrait;

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

    // Ensure that the customer has an email (for dunning emails).
    $this->user->setEmail($this->randomMachineName() . '@example.com');
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
    $new_time = strtotime('2017-02-24 19:00');
    $this->rewindTime($new_time);
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
    // Confirm dunning email.
    $this->assertMailString('subject', 'Payment declined - Order #1.', 1);
    $this->assertMailString('body', 'We regret to inform you that the most recent charge attempt on your card failed.', 1);
    $this->assertMailString('body', Url::fromRoute('entity.commerce_payment_method.collection', ['user' => 1], ['absolute' => TRUE])->toString(), 1);
    $next_retry_time = strtotime('+1 day', $new_time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    // Run the first retry.
    $new_time = strtotime('2017-02-25 19:00');
    $this->rewindTime($new_time);
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
    // Confirm dunning email.
    $next_retry_time = strtotime('+3 days', $new_time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    // Run the second retry.
    $new_time = strtotime('2017-02-28 19:00');
    $this->rewindTime($new_time);
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
    // Confirm dunning email.
    $next_retry_time = strtotime('+5 days', $new_time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    // Run the last retry.
    $new_time = strtotime('2017-03-05 19:00');
    $this->rewindTime($new_time);
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
    // Confirm dunning email.
    $this->assertMailString('body', 'Our final charge attempt has failed.', 1);
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

    // Reset services so that new time gets injected.
    $new_time_c = date('c', $new_time);
    $rm = \Drupal::service('commerce_recurring.recurring_mail');
    $time = date('c', $rm->time->getCurrentTime());
    $this->container = \Drupal::getContainer();
    $this->container->set('commerce_recurring.recurring_mail', NULL);
    $this->container->set('commerce_recurring.event_subscriber.dunning_subscriber', NULL);
    $rm = \Drupal::service('commerce_recurring.recurring_mail');
    $time = date('c', $rm->time->getCurrentTime());
  }

}
