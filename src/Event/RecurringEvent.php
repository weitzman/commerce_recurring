<?php

namespace Drupal\commerce_recurring\Event;

use Drupal\commerce_recurring\Entity\RecurringInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the recurring event.
 *
 * @see \Drupal\commerce_recurring\Event\RecurringEvents
 */
class RecurringEvent extends Event {

  /**
   * The recurring.
   *
   * @var \Drupal\commerce_recurring\Entity\RecurringInterface
   */
  protected $recurring;

  /**
   * Constructs a new RecurringEvent event.
   *
   * @param \Drupal\commerce_recurring\Entity\RecurringInterface $recurring
   *   The recurring.
   */
  public function __construct(RecurringInterface $recurring) {
    $this->recurring = $recurring;
  }

  /**
   * Gets the recurring.
   *
   * @return \Drupal\commerce_recurring\Entity\RecurringInterface
   *   Gets the recurring.
   */
  public function get() {
    return $this->recurring;
  }

}
