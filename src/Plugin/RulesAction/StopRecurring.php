<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Deactivate the recurring entity' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_generate_recurring_product",
 *   label = @Translation("Deactivate the recurring entity"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "data" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity to disable the recurring from."),
 *       assignment_restriction = "selector"
 *     ),
 *   }
 * )
 */
class StopRecurring extends RulesActionBase {

  /**
   * The main commerce recurring controller.
   *
   * @var \Drupal\commerce_recurring\Controller\RecurringController
   */
  protected $recurringController;

  /**
   * {@inheritdoc}
   */
  public function __construct(RecurringController $recurring_controller, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->recurringController = $recurring_controller;
  }

  /**
   * Generate the order associated to the order recurring entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The commerce recurring entity.
   */
  protected function doExecute(EntityInterface $entity) {
    // @todo Finish this action.
  }

}
