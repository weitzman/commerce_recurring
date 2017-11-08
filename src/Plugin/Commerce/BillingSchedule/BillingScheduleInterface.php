<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides the interface for billing schedules.
 *
 * Responsible for generating billing cycles, used to determine when the
 * customer should be charged.
 *
 * @see \Drupal\commerce_recurring\BillingCycle
 */
interface BillingScheduleInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the billing schedule label.
   *
   * @return string
   *   The billing schedule label.
   */
  public function getLabel();

  /**
   * Generates the first billing cycle.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The billing start date/time.
   *
   * @return \Drupal\commerce_recurring\BillingCycle
   *   The billing cycle.
   */
  public function generateFirstBillingCycle(DrupalDateTime $start_date);

  /**
   * Generates the next billing cycle.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The billing start date/time.
   * @param \Drupal\commerce_recurring\BillingCycle $billing_cycle
   *   The current billing cycle.
   *
   * @return \Drupal\commerce_recurring\BillingCycle
   *   The billing cycle.
   */
  public function generateNextBillingCycle(DrupalDateTime $start_date, BillingCycle $billing_cycle);

}
