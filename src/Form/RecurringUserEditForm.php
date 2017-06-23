<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for user editing recurrings.
 */
class RecurringUserEditForm extends FormBase {

  /**
   * The current recurring.
   *
   * @var \Drupal\commerce_recurring\Entity\Recurring
   */
  protected $recurring;

  /**
   * The current user.
   *
   * @var \Drupal\user\Entity\User.
   */
  protected $user;

  /**
   * Constructs a new RecurringUserEditForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->recurring = $current_route_match->getParameter('commerce_recurring');
    $this->user = $current_route_match->getParameter('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_recurring_user_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['due_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Next due date'),
      '#default_value' => $this->recurring->getDueDateTime() !== NULL ? DrupalDateTime::createFromTimestamp($this->recurring->getDueDateTime()) : NULL,
      '#required' => TRUE,
    ];
    $form['end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End date'),
      '#default_value' => $this->recurring->getEndDateTime() !== NULL ? DrupalDateTime::createFromTimestamp($this->recurring->getEndDateTime()) : NULL,
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        'enable' => t('Enabled'),
        'disable' => t('Disabled'),
      ],
      '#default_value' => $this->recurring->isEnabled() ? 'enable' : 'disable',
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \DateTime $due_date */
    $due_date = $values['due_date'];
    $due_date = $due_date->getTimestamp();
    $form_state->setValue('due_date', $due_date);

    $end_date = NULL;
    if ($this->recurring->getEndDateTime() !== NULL) {
      /** @var \DateTime $end_date */
      $end_date = $values['end_date'];
      $end_date = $end_date->getTimestamp();
      $form_state->setValue('end_date', $end_date);

      if ($end_date < $due_date) {
        $form_state->setErrorByName('due_date', $this->t('The next due date must be higher than end date.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->recurring->setDueDateTime($values['due_date']);
    $this->recurring->setEndDateTime($values['end_date']);
    $this->recurring->setEnabled($values['status']);

    $this->recurring->save();

    drupal_set_message($this->t('The recurring %label has been successfully saved.', ['%label' => $this->recurring->label()]));
  }

}
