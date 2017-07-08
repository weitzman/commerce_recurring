<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Get the commerce recurrings about to due' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_set_order_item_price",
 *   label = @Translation("Get the commerce recurrings about to due"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "number_items" = @ContextDefinition("decimal",
 *       label = @Translation("Number of items"),
 *       description = @Translation("Restrict the number of items to retrieve")
 *     ),
 *     "timestamp" = @ContextDefinition("date",
 *       label = @Translation("Due date"),
 *     )
 *   },
 *   provides = {
 *     "commerce_recurrings" = @ContextDefinition(
 *       "list<commerce_recurring>",
 *       label = @Translation("Commerce Recurrings")
 *     )
 *   }
 * )
 */
class GetDueRecurrings extends RulesActionBase {

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
   * Get the commerce recurring orders about to due.
   *
   * @param int $number_items
   *   The number of items to retrieve.
   * @param int $timestamp
   *   The due date.
   */
  protected function doExecute($number_items, $timestamp = NULL) {
    $commerce_recurrings = $this->recurringController->getDueRecurrings($number_items, $timestamp);

    $this->setProvidedValue('commerce_recurrings', $commerce_recurrings);
  }

}
