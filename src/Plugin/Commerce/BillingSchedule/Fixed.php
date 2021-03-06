<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a fixed interval billing schedule.
 *
 * Billing cycles generated by this plugin always start at the beginning
 * of the configured interval. For example, if a monthly subscription is
 * opened on Oct 12th, the generated billing cycle will be Oct 1st - Nov 1st.
 *
 * @CommerceBillingSchedule(
 *   id = "fixed",
 *   label = @Translation("Fixed interval"),
 * )
 */
class Fixed extends IntervalBase {

  /**
   * {@inheritdoc}
   */
  public function generateFirstBillingCycle(DrupalDateTime $start_date) {
    // @todo $start_date->setTimezone($site_timezone)
    $interval = $this->getInterval();
    return new BillingCycle($interval->floor($start_date), $interval->ceil($start_date));
  }

  /**
   * {@inheritdoc}
   */
  public function generateNextBillingCycle(DrupalDateTime $start_date, BillingCycle $billing_cycle) {
    $next_start_date = $billing_cycle->getEndDate();
    return new BillingCycle($next_start_date, $this->getInterval()->add($next_start_date));
  }

}
