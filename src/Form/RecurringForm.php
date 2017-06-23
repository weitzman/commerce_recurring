<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the commerce_recurring entity edit forms.
 */
class RecurringForm extends ContentEntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new RecurringForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, EntityManagerInterface $entity_manager) {
    parent::__construct($entity_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_recurring\Entity\Recurring $recurring */
    $recurring = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = 'commerce_recurring_edit_form';
    $form['#attached']['library'][] = 'commerce_recurring/form';
    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $recurring->getChangedTime(),
    ];

    $last_saved = $this->dateFormatter->format($recurring->getChangedTime(), 'short');
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'status' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $recurring->isEnabled() ? t('Enabled') : t('Disabled'),
        '#attributes' => [
          'class' => 'entity-meta__title',
        ],
      ],
      'changed' => $this->fieldAsReadOnly($this->t('Last saved'), $last_saved),
    ];
    $form['customer'] = [
      '#type' => 'details',
      '#title' => t('Customer information'),
      '#group' => 'advanced',
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['recurring-form-author'],
      ],
      '#weight' => 91,
    ];

    // Show the recurring's store only if there are multiple available.
    $store_query = $this->entityTypeManager->getStorage('commerce_store')->getQuery();
    $store_count = $store_query->count()->execute();
    if ($store_count > 1) {
      $store_link = $recurring->getStore()->toLink()->toString();
      $form['meta']['store'] = $this->fieldAsReadOnly($this->t('Store'), $store_link);
    }
    // Move uid/mail widgets to the sidebar, or provide read-only alternatives.
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'customer';
    }
    else {
      $user_link = $recurring->getOwner()->toLink()->toString();
      $form['customer']['uid'] = $this->fieldAsReadOnly($this->t('Customer'), $user_link);
    }
    if (isset($form['mail'])) {
      $form['mail']['#group'] = 'customer';
    }
    elseif (!empty($recurring->getEmail())) {
      $form['customer']['mail'] = $this->fieldAsReadOnly($this->t('Contact email'), $recurring->getEmail());
    }
    // All additional customer information should come after uid/mail.
    $form['customer']['ip_address'] = $this->fieldAsReadOnly($this->t('IP address'), $recurring->getIpAddress());

    return $form;
  }

  /**
   * Builds a read-only form element for a field.
   *
   * @param string $label
   *   The element label.
   * @param string $value
   *   The element value.
   *
   * @return array
   *   The form element.
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => [Html::cleanCssIdentifier(strtolower($label)), 'container-inline'],
      ],
      '#markup' => '<h4 class="label inline">' . $label . '</h4> ' . $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring */
    $recurring = parent::validateForm($form, $form_state);

    $start_date = $recurring->getStartDateTime();
    $end_date = $recurring->getEndDateTime();
    $due_date = $recurring->getDueDateTime();

    if ($start_date > $due_date || empty($due_date)) {
      $form_state->setErrorByName('due_date', $this->t('The next due date must be higher than start date.'));
    }
    if ($start_date >= $end_date && !empty($end_date)) {
      $form_state->setErrorByName('end_date', $this->t('The end date must be higher than start date.'));
    }

    return $recurring;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The recurring %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_recurring.collection');
  }

}
