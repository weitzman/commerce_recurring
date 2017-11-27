<?php

namespace Drupal\commerce_recurring\EventSubscriber;

use Drupal\commerce_recurring\Event\PaymentDeclinedEvent;
use Drupal\commerce_recurring\Event\RecurringEvents;
use Drupal\commerce_recurring\RecurringMail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Commerce Dunning event subscriber.
 */
class DunningSubscriber implements EventSubscriberInterface {

  protected $recurringMail;

  /**
   * Constructs a new DunningSubscriber object.
   */
  public function __construct(RecurringMail $recurring_mail) {
    $this->recurringMail = $recurring_mail;
  }

  /**
   * Sends a payment declined email.
   *
   * @param \Drupal\commerce_recurring\Event\PaymentDeclinedEvent $event
   *   The event we subscribed to.
   */
  public function sendPaymentDeclined(PaymentDeclinedEvent $event) {
    $this->recurringMail->sendPaymentDeclined($event->getOrder(), $event->getRetryDays(), $event->getNumRetries(), $event->getMaxRetries());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [RecurringEvents::PAYMENT_DECLINED => ['sendPaymentDeclined', -100]];
    return $events;
  }

}
