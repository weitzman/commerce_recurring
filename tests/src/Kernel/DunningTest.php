<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;

/**
 * @group commerce_recurring
 */
class DunningTest extends RecurringKernelTestBase {

  use AssertMailTrait;

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
      'starts' => strtotime('2017-02-24 17:00'),
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
      'starts' => strtotime('2017-02-25 17:00:00'),
    ]);
    $second_subscription->save();

    // Rewind time to the end of the first subscription.
    // Confirm that only the first subscription's order was queued.
    // $this->rewindTime(strtotime('2017-02-24 19:00'));
    $this->container->get('commerce_recurring.cron')->run();

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = Queue::load('commerce_recurring');

    $job1 = $queue->getBackend()->claimJob();

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
    $next_retry_time = strtotime('+3 days');
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    // @todo Second retry. Not the line below is not working. Job is not available yet.
    $this->rewindTime($next_retry_time + 100);
    $job1 = $queue->getBackend()->claimJob();
    $processor->processJob($job1, $queue);
    $next_retry_time = strtotime('+3 days', $next_retry_time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);


  }

}
