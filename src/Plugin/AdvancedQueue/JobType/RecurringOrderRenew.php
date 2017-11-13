<?php

namespace Drupal\commerce_recurring\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_recurring\RecurringOrderManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @AdvancedQueueJobType(
 *   id = "commerce_recurring_order_renew",
 *   label = @Translation("Recurring order renew"),
 * )
 */
class RecurringOrderRenew extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * Constructs a new RecurringOrderRenew object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   *   The recurring order manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RecurringOrderManagerInterface $recurring_order_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->recurringOrderManager = $recurring_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_recurring.order_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $order_id = $job->getPayload()['order_id'];
    $order = Order::load($order_id);
    $this->recurringOrderManager->renewOrder($order);
  }

}
