<?php

namespace Drupal\commerce_recurring\Event;

/**
 * Defines events for the recurring order entity.
 */
final class RecurringEvents {

  /**
   * Name of the event fired after loading a recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_LOAD = 'commerce_recurring.commerce_recurring.load';

  /**
   * Name of the event fired after creating a new recurring order.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_CREATE = 'commerce_recurring.commerce_recurring.create';

  /**
   * Name of the event fired before saving a recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_PRESAVE = 'commerce_recurring.commerce_recurring.presave';

  /**
   * Name of the event fired after saving a new recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_INSERT = 'commerce_recurring.commerce_recurring.insert';

  /**
   * Name of the event fired after saving an existing order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_UPDATE = 'commerce_recurring.commerce_recurring.update';

  /**
   * Name of the event fired before deleting a recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_PREDELETE = 'commerce_recurring.commerce_recurring.predelete';

  /**
   * Name of the event fired after deleting a recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_DELETE = 'commerce_recurring.commerce_recurring.delete';

  /**
   * Name of the event fired after stopping a recurring order.
   *
   * @Event
   *
   * @see \Drupal\commerce_recurring\Event\RecurringEvent
   */
  const RECURRING_STOP = 'commerce_recurring.commerce_recurring.stop';

}
