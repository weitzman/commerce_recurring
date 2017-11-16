<?php

namespace Drupal\commerce_recurring\Event;

final class RecurringEvents {
  /**
   * Name of the event fired when a payment is declined.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType\RecurringOrderClose::process
   */
  const PAYMENT_DECLINED = 'commerce_recurring.payment_declined';
}
