<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a recurring type.
 */
class RecurringTypeDeleteForm extends EntityDeleteForm {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage service.
   */
  protected $recurringStorage;

  /**
   * Constructs a new RecurringTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->recurringStorage = $entityTypeManager->getStorage('commerce_recurring');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $recurring_count = $this->recurringStorage->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if ($recurring_count) {
      $caption = '<p>' . $this->formatPlural($recurring_count, '%type is used by 1 recurring on your site. You can not remove this recurring type until you have removed all of the %type recurrings.', '%type is used by @count recurrings on your site. You may not remove %type until you have removed all of the %type recurrings.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
