<?php

namespace Drupal\commerce_recurring_test\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\BillingScheduleBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;

/**
 * @CommerceBillingSchedule(
 *   id = "test_plugin",
 *   label = "Test label"
 * )
 */
class TestPlugin extends BillingScheduleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['key' => 'value'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $this->configuration['key'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['key'] = $values['key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateFirstBillingPeriod(DrupalDateTime $start_date) {
    $end_date = clone $start_date;
    $end_date->modify('+50 seconds');
    return new BillingPeriod($start_date, $end_date);
  }

  /**
   * {@inheritdoc}
   */
  public function generateNextBillingPeriod(DrupalDateTime $start_date, BillingPeriod $billing_period) {
    $end_date = clone $billing_period->getEndDate();
    $end_date->modify('+50 seconds');
    return new BillingPeriod($billing_period->getEndDate(), $end_date);
  }

}
