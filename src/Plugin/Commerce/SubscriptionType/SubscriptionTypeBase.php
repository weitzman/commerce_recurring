<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_recurring\BillingCycle;
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
  public function createRecurringOrder(SubscriptionInterface $subscription) {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');

    $start_date = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    $initial_billing_cycle = $subscription->getBillingSchedule()
      ->getPlugin()
      ->generateFirstBillingCycle($start_date);
    $initial_charges = $subscription->getType()->collectCharges($initial_billing_cycle, $subscription);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->create([
      'type' => 'recurring',
      'uid' => $subscription->getCustomer(),
      // @todo Is this the right store?
      'store_id' => \Drupal::service('commerce_store.current_store')->getStore(),
      'billing_cycle' => $initial_billing_cycle,
    ]);

    foreach ($initial_charges as $charge) {
      // Create the initial order item.
      // @todo Take into account prepaid vs. postpaid
      $order_item = $order_item_storage->createFromPurchasableEntity($subscription, [
        'type' => 'recurring',
        'title' => $charge->getLabel(),
        'billing_schedule' => $subscription->getBillingSchedule(),
        'quantity' => 1,
        'unit_price' => $charge->getAmount(),
        'started' => $charge->getStartTime()->format('U'),
        'ended' => $charge->getEndTime()->format('U'),
      ]);

      $order_item->save();
      $order->addItem($order_item);
    }

    $order->save();
    return $order;
  }

  /**
   * {@inheritdoc}
   */
  public function renewRecurringOrder(SubscriptionInterface $subscription, OrderInterface $previous_recurring_order) {
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');

    $start_date = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_item */
    $billing_cycle_item = $previous_recurring_order->get('billing_cycle')->first();
    $current_billing_cycle = $billing_cycle_item->toBillingCycle();
    $next_billing_cycle = $subscription->getBillingSchedule()->getPlugin()->generateNextBillingCycle($start_date, $current_billing_cycle);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $next_order */
    $next_order = $order_storage->create([
      'type' => 'recurring',
      'uid' => $subscription->getCustomerId(),
      'store_id' => $previous_recurring_order->getStore(),
      'billing_cycle' => $next_billing_cycle,
    ]);

    $charges = $this->collectCharges($next_billing_cycle, $subscription);
    foreach ($charges as $charge) {
      $order_item = $order_item_storage->createFromPurchasableEntity($subscription, [
        'type' => 'recurring',
        'billing_schedule' => $subscription->getBillingSchedule(),
        'quantity' => 1,
        'unit_price' => $charge->getAmount(),
      ]);
      $order_item->save();
      $next_order->addItem($order_item);
    }
    $next_order->save();
    return $next_order;
  }

}
