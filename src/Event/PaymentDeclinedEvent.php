<?php

namespace Drupal\commerce_recurring\Event;

use Drupal\advancedqueue\Job;
use Drupal\commerce_order\Entity\Order;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for sending payment decline emails.
 *
 * @see \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\RecurringOrderClose::process
 */
class PaymentDeclinedEvent extends Event {

  /**
   * @var Order $payment
   */
  protected $order;

  /**
   * @var integer
   */
  protected $retry_days;

  /**
   * @var \Drupal\advancedqueue\Job
   */
  protected $job;

  public function __construct(Order $order, int $retry_days, Job $job) {
    $this->order = $order;
    $this->retry_days = $retry_days;
    $this->job = $job;
  }

  /**
   * @return \Drupal\advancedqueue\Job
   */
  public function getJob() {
    return $this->job;
  }

  /**
   * @return integer
   */
  public function getRetrydays() {
    return $this->retry_days;
  }

  /**
   * @return \Drupal\commerce_order\Entity\Order
   */
  public function getOrder() {
    return $this->order;
  }
}
