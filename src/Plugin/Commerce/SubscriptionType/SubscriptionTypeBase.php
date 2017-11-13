<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\BillingCycle;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the subscription base class.
 */
abstract class SubscriptionTypeBase extends PluginBase implements SubscriptionTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SubscriptionTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntityTypeId() {
    if (!empty($this->pluginDefinition['purchasable_entity_type'])) {
      return $this->pluginDefinition['purchasable_entity_type'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingCycle $billing_cycle) {
    $billing_type = $subscription->getBillingSchedule()->getBillingType();
    if ($billing_type == BillingScheduleInterface::BILLING_TYPE_PREPAID) {
      $start_date = new DrupalDateTime($subscription->getStartTime());
      $billing_schedule = $subscription->getBillingSchedule()->getPlugin();
      $next_billing_cycle = $billing_schedule->generateNextBillingCycle($start_date, $billing_cycle);
      // The initial order (which starts the subscription) pays the first
      // billing cycle, so the base charge is always for the next one.
      // The October recurring order (ending on Nov 1st) charges for November.
      $base_charge = new Charge([
        'purchased_entity' => $subscription->getPurchasedEntity(),
        'title' => $subscription->getTitle(),
        'quantity' => $subscription->getQuantity(),
        'unit_price' => $subscription->getUnitPrice(),
        'start_date' => $next_billing_cycle->getStartDate(),
        'end_date' => $next_billing_cycle->getEndDate(),
      ]);
    }
    else {
      // Postpaid means we're always charging for the current billing cycle.
      // The October recurring order (ending on Nov 1st) charges for October.
      $base_charge = new Charge([
        'purchased_entity' => $subscription->getPurchasedEntity(),
        'title' => $subscription->getTitle(),
        'quantity' => $subscription->getQuantity(),
        'unit_price' => $subscription->getUnitPrice(),
        'start_date' => $billing_cycle->getStartDate(),
        'end_date' => $billing_cycle->getEndDate(),
      ]);
    }

    return [$base_charge];
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCreate(SubscriptionInterface $subscription, OrderItemInterface $order_item) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionActivate(SubscriptionInterface $subscription, OrderInterface $order) {}

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionRenew(SubscriptionInterface $subscription, OrderInterface $order, OrderInterface $next_order) {}

}
