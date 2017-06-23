<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Create/Update a recurring order from line items' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_generate_recurring_order",
 *   label = @Translation("Create/Update a recurring order from line items"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Commerce Order"),
 *       description = @Translation("Specifies the recurring commerce order, which should be created/updated.")
 *     ),
 *     "commerce_line_items" = @ContextDefinition("list<commerce_line_item>",
 *       label = @Translation("Commerce Line items"),
 *     )
 *   }
 * )
 */
class GenerateRecurringFromOrder extends RulesActionBase {

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
   * Create/Update a recurring order from line items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order entity.
   * @param array $commerce_line_items
   *   The commerce line items entity.
   */
  protected function doExecute(OrderInterface $commerce_order, array $commerce_line_items = []) {
    // @todo Finish this action.
  }

}
