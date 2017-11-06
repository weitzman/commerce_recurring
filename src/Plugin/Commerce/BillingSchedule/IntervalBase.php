<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce\Interval;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for interval-based billing schedules.
 */
abstract class IntervalBase extends BillingScheduleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number' => 1,
      'unit' => 'month',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number'),
      '#default_value' => $this->configuration['number'],
    ];
    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => [
        'hour' => $this->t('Hour'),
        'day' => $this->t('Day'),
        'week' => $this->t('Week'),
        'month' => $this->t('Month'),
        'year' => $this->t('Year'),
      ],
      '#default_value' => $this->configuration['unit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration = [];
      $this->configuration['number'] = $values['number'];
      $this->configuration['unit'] = $values['unit'];
    }
  }

  /**
   * Gets the current interval.
   *
   * @return \Drupal\commerce\Interval
   *   The interval.
   */
  protected function getInterval() {
    return new Interval($this->configuration['number'], $this->configuration['unit']);
  }

}
