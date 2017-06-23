<?php

namespace Drupal\commerce_recurring\Plugin\RulesAction;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\Controller\RecurringController;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Get all the recurring orders on a commerce order' action.
 *
 * @RulesAction(
 *   id = "commerce_recurring_get_recurring_orders_on_an_order",
 *   label = @Translation("Get all the recurrings on a commerce order"),
 *   category = @Translation("Commerce Recurring"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("commerce_order",
 *       label = @Translation("Commerce Order"),
 *       description = @Translation("Specifies the commerce order for which to do the action.")
 *     )
 *   },
 *   provides = {
 *     "commerce_recurrings" = @ContextDefinition(
 *       "list<commerce_recurring>",
 *       label = @Translation("Commerce Recurring entities")
 *     )
 *   }
 * )
 */
class GetRecurringsOnAnOrder extends RulesActionBase {

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
   * Get all the recurrings on a commerce order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order entity.
   */
  protected function doExecute(OrderInterface $commerce_order) {
    $commerce_recurrings = $this->recurringController->getRecurringsOnAnOrder($commerce_order);

    $this->setProvidedValue('commerce_recurrings', $commerce_recurrings);
  }

}
