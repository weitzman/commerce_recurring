<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for billing schedules.
 *
 * This configuration entity stores configuration for billing schedule plugins.
 */
interface BillingScheduleInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Available billing types.
   */
  const BILLING_TYPE_PREPAID = 'prepaid';
  const BILLING_TYPE_POSTPAID = 'postpaid';

  /**
   * Available dunning dispositions.
   */
  const DUNNING_DISPOSITION_SUSPEND = 'suspend';
  const DUNNING_DISPOSITION_CANCEL = 'cancel';

  /**
   * Gets the display label.
   *
   * This label is customer-facing.
   *
   * @return string
   *   The display label.
   */
  public function getDisplayLabel();

  /**
   * Sets the display label.
   *
   * @param string $display_label
   *   The display label.
   *
   * @return $this
   */
  public function setDisplayLabel($display_label);

  /**
   * Gets the billing type.
   *
   * The billing type can be either:
   * - Prepaid: Subscription is paid at the beginning of the period.
   * - Postpaid: Subscription is paid at the end of the period.
   *
   * @return string
   *   The billing type, one of the BILLING_TYPE_ constants.
   */
  public function getBillingType();

  /**
   * Sets the billing type.
   *
   * @param string $billing_type
   *   The billing type.
   *
   * @return $this
   */
  public function setBillingType($billing_type);

  /**
   * Gets the dunning schedule.
   *
   * @return array
   *   The dunning schedule steps.
   */
  public function getDunningSchedule();

  /**
   * Sets the dunning schedule.
   *
   * @param array $schedule
   *   The schedule.
   *
   * @return $this
   */
  public function setDunningSchedule($schedule);

  /**
   * Gets the dunning disposition.
   *
   * The disposition can be either:
   * - Suspend: Subscription is suspended but can be unsuspended.
   * - Cancel: Subscription is cancelled and may not be reversed.
   *
   * @return string
   *   The disposition, one of the DUNNING_DISPOSITION_ constants.
   */
  public function getDunningDisposition();

  /**
   * Sets the dunning disposition.
   *
   * @param string $disposition
   *   The disposition.
   *
   * @return $this
   */
  public function setDunningDisposition($disposition);

  /**
   * Gets the billing schedule plugin ID.
   *
   * @return string
   *   The billing schedule plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the billing schedule plugin ID.
   *
   * @param string $plugin_id
   *   The billing schedule plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the billing schedule plugin configuration.
   *
   * @return string
   *   The billing schedule plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the billing schedule plugin configuration.
   *
   * @param array $configuration
   *   The billing schedule plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Gets the billing schedule plugin.
   *
   * @return \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\BillingScheduleInterface
   *   The billing schedule plugin.
   */
  public function getPlugin();

}
