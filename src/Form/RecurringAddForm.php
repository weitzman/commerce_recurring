<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the recurring add form.
 */
class RecurringAddForm extends FormBase {

  use RecurringCustomerFormTrait;

  /**
   * The recurring storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $recurringStorage;

  /**
   * The store storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $storeStorage;

  /**
   * Constructs a new RecurringAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->recurringStorage = $entity_type_manager->getStorage('commerce_recurring');
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_recurring_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->storeStorage->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Recurrings can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];

      return $form;
    }

    $form['type'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Recurring type'),
      '#target_type' => 'commerce_recurring_type',
      '#required' => TRUE,
    ];
    $form['store_id'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Store'),
      '#target_type' => 'commerce_store',
      '#required' => TRUE,
    ];
    $form = $this->buildCustomerForm($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitCustomerForm($form, $form_state);

    $values = $form_state->getValues();
    $recurring_data = [
      'type' => $values['type'],
      'mail' => $values['mail'],
      'uid' => [$values['uid']],
      'store_id' => [$values['store_id']],
      'start_date' => REQUEST_TIME,
    ];
    $recurring = $this->recurringStorage->create($recurring_data);
    $recurring->save();

    // Redirect to the edit form to complete the recurring.
    $form_state->setRedirect('entity.commerce_recurring.edit_form', ['commerce_recurring' => $recurring->id()]);
  }

}
