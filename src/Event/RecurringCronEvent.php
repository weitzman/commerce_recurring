<?php

namespace Drupal\commerce_recurring\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired when commerce recurring cron is executed.
 *
 * @see commerce_recurring_cron()
 */
class RecurringCronEvent extends GenericEvent {

  const EVENT_NAME = 'commerce_recurring_cron';

}
