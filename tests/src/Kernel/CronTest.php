<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Cron
 * @group commerce_recurring
 */
class CronTest extends RecurringKernelTestBase {

  use AssertMailTrait;

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
    $new_time = $first_subscription->get('starts')->value + 100;
    $this->rewindTime($new_time);
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
    $processor->processJob($job1, $queue);

    $mails = $this->getMails();
    $this->assertEquals(1, count($mails));

    $the_email = reset($mails);
    $this->assertEquals('text/html; charset=UTF-8;', $the_email['headers']['Content-Type']);
    $this->assertEquals('Payment declined - Order #1.', $the_email['subject']);
    $this->assertEmpty(isset($the_email['headers']['Bcc']));
    $this->assertMailString('body', 'We regret to inform you that the most recent charge attempt on your card failed.', 1);
    $this->assertMailString('body', Url::fromRoute('entity.commerce_payment_method.collection', ['user' => 1], ['absolute' => true])->toString(), 1);
    // $this->verboseEmail();
    $next_retry_time = strtotime("+3 days", $new_time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    // @todo Second retry. Not working yet
    $this->rewindTime($next_retry_time + 100);
    $this->container->get('commerce_recurring.cron')->run();
    $job1 = $queue->getBackend()->claimJob();
    $processor->processJob($job1, $queue);


  }

}
