<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides the recurring type add form.
 */
class RecurringTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $recurring_type = $this->entity;

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $recurring_type->label(),
      '#description' => $this->t('Label for the recurring type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $recurring_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_recurring\Entity\RecurringType::load',
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $this \Drupal\commerce_recurring\Entity\RecurringTypeInterface */
    $status = $this->entity->save();
    drupal_set_message($this->t('Saved the %label recurring type.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_recurring_type.collection');

    if ($status == SAVED_NEW) {
      commerce_recurring_add_order_items_field($this->entity);
      commerce_recurring_add_recurring_orders_field($this->entity);
    }
  }

}
