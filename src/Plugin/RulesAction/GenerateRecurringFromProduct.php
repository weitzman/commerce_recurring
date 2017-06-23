<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_price\Price;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Create/Update a recurring entity from product data' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_generate_recurring_product",
 *   label = @Translation("Create/Update a recurring order from product data"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Commerce Order"),
 *       description = @Translation("Specifies the recurring commerce order, which should be created/updated.")
 *     ),
 *     "commerce_line_item" = @ContextDefinition("commerce_line_item",
 *       label = @Translation("Commerce Line Item"),
 *     ),
 *     "fixed_price" = @ContextDefinition("commerce_price",
 *       label = @Translation("Fixed price for the recurring entity"),
 *     ),
 *     "quantity" = @ContextDefinition("decimal",
 *       label = @Translation("Quantity"),
 *     )
 *   }
 * )
 */
class GenerateRecurringFromProduct extends RulesActionBase {

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
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order entity.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $commerce_line_item
   *   The commerce line item entity.
   * @param \Drupal\commerce_price\Price $fixed_price
   *   The fixed price object for the recurring entity.
   * @param int $quantity
   *   The quantity for the line item units.
   */
  protected function doExecute(OrderInterface $commerce_order, LineItemInterface $commerce_line_item, Price $fixed_price, $quantity) {
    // @todo Finish this action.
  }

}
