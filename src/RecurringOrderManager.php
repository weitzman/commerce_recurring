<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default recurring order manager.
 *
 * Currently assumes that there's a single subscription per recurring order.
 */
class RecurringOrderManager implements RecurringOrderManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The order item prorater.
   *
   * @var \Drupal\commerce_recurring\OrderItemProraterInterface
   */
  protected $orderItemProrater;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new RecurringOrderManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_recurring\OrderItemProraterInterface $order_item_prorater
   *   The order item prorater.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OrderItemProraterInterface $order_item_prorater, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->orderItemProrater = $order_item_prorater;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureOrder(SubscriptionInterface $subscription) {
    $start_date = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    $billing_schedule = $subscription->getBillingSchedule();
    $billing_period = $billing_schedule->getPlugin()->generateFirstBillingPeriod($start_date);
    $order = $this->createOrder($subscription, $billing_period);
    $this->applyCharges($order, $subscription, $billing_period);
    // Allow the type to modify the subscription and order before they're saved.
    $subscription->getType()->onSubscriptionActivate($subscription, $order);

    // @todo The order should save its own unsaved order items.
    foreach ($order->getItems() as $order_item) {
      $order_item->save();
    }
    $order->save();
    $subscription->addOrder($order);
    $subscription->save();

    return $order;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshOrder(OrderInterface $order) {
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_item */
    $billing_period_item = $order->get('billing_period')->first();
    $billing_period = $billing_period_item->toBillingPeriod();
    $subscriptions = $this->collectSubscriptions($order);
    $payment_method = $this->selectPaymentMethod($subscriptions);
    $billing_profile = $payment_method ? $payment_method->getBillingProfile() : NULL;
    $payment_gateway_id = $payment_method ? $payment_method->getPaymentGatewayId() : NULL;

    $order->set('billing_profile', $billing_profile);
    $order->set('payment_method', $payment_method);
    $order->set('payment_gateway', $payment_gateway_id);
    foreach ($subscriptions as $subscription) {
      $this->applyCharges($order, $subscription, $billing_period);
    }
    // The same workaround that \Drupal\commerce_order\OrderRefresh does.
    foreach ($order->getItems() as $order_item) {
      if ($order_item->isNew()) {
        $order_item->order_id->entity = $order;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function closeOrder(OrderInterface $order) {
    if ($order->getState()->value == 'draft') {
      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();
    }

    $subscriptions = $this->collectSubscriptions($order);
    $payment_method = $this->selectPaymentMethod($subscriptions);
    if (!$payment_method) {
      throw new HardDeclineException('Payment method not found.');
    }
    $payment_gateway = $payment_method->getPaymentGateway();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $payment_storage->create([
      'payment_gateway' => $payment_gateway->id(),
      'payment_method' => $payment_method->id(),
      'order_id' => $order->id(),
      'amount' => $order->getTotalPrice(),
      'state' => 'new',
    ]);
    // The createPayment() call might throw a decline exception, which is
    // supposed to be handled by the caller, to allow for dunning.
    $payment_gateway_plugin->createPayment($payment);

    $transition = $order->getState()->getWorkflow()->getTransition('mark_paid');
    $order->getState()->applyTransition($transition);
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function renewOrder(OrderInterface $order) {
    $subscriptions = $this->collectSubscriptions($order);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);
    $billing_schedule = $subscription->getBillingSchedule();
    $start_date = DrupalDateTime::createFromTimestamp($subscription->getStartTime());
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $billing_period_item */
    $billing_period_item = $order->get('billing_period')->first();
    $current_billing_period = $billing_period_item->toBillingPeriod();
    $next_billing_period = $billing_schedule->getPlugin()->generateNextBillingPeriod($start_date, $current_billing_period);

    $next_order = $this->createOrder($subscription, $next_billing_period);
    $this->applyCharges($next_order, $subscription, $next_billing_period);
    // Allow the subscription type to modify the order before it is saved.
    $subscription->getType()->onSubscriptionRenew($subscription, $order, $next_order);

    // @todo The order should save its own unsaved order items.
    foreach ($next_order->getItems() as $order_item) {
      $order_item->save();
    }
    $next_order->save();
    // Update the subscription with the new order and renewal timestamp.
    $subscription->addOrder($next_order);
    $subscription->setRenewedTime($this->time->getCurrentTime());
    $subscription->save();

    return $next_order;
  }

  /**
   * {@inheritdoc}
   */
  public function collectSubscriptions(OrderInterface $order) {
    $subscriptions = [];
    foreach ($order->getItems() as $order_item) {
      if ($order_item->get('subscription')->isEmpty()) {
        // There should never be a recurring order item without a subscription.
        continue;
      }
      /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
      $subscription = $order_item->get('subscription')->entity;
      $subscriptions[$subscription->id()] = $subscription;
    }

    return $subscriptions;
  }

  /**
   * Creates a recurring order for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The created recurring order, unsaved.
   */
  protected function createOrder(SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    $payment_method = $subscription->getPaymentMethod();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->create([
      'type' => 'recurring',
      'store_id' => $subscription->getStoreId(),
      'uid' => $subscription->getCustomerId(),
      'billing_profile' => $payment_method ? $payment_method->getBillingProfile() : NULL,
      'payment_method' => $payment_method,
      'payment_gateway' => $payment_method ? $payment_method->getPaymentGatewayId() : NULL,
      'billing_period' => $billing_period,
      'billing_schedule' => $subscription->getBillingSchedule(),
    ]);

    return $order;
  }

  /**
   * Applies subscription charges to the given recurring order.
   *
   * Note: The order items are not saved.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   */
  protected function applyCharges(OrderInterface $order, SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $existing_order_items = [];
    foreach ($order->getItems() as $order_item) {
      if ($order_item->get('subscription')->target_id == $subscription->id()) {
        $existing_order_items[] = $order_item;
      }
    }

    $order_items = [];
    $charges = $subscription->getType()->collectCharges($subscription, $billing_period);
    foreach ($charges as $charge) {
      $order_item = array_shift($existing_order_items);
      if (!$order_item) {
        /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
        $order_item = $order_item_storage->create([
          'type' => $this->getOrderItemTypeId($subscription),
          'subscription' => $subscription->id(),
        ]);
      }

      // @todo Add a purchased_entity setter to OrderItemInterface.
      $order_item->set('purchased_entity', $charge->getPurchasedEntity());
      $order_item->setTitle($charge->getTitle());
      $order_item->setQuantity($charge->getQuantity());
      $order_item->set('billing_period', $charge->getBillingPeriod());
      // Populate the initial unit price, then prorate it.
      $order_item->setUnitPrice($charge->getUnitPrice());
      $prorated_unit_price = $this->orderItemProrater->prorateRecurring($order_item, $billing_period);
      $order_item->setUnitPrice($prorated_unit_price, TRUE);

      $order_items[] = $order_item;
    }
    $order->setItems($order_items);

    // Delete any previous leftover order items.
    if ($existing_order_items) {
      $order_item_storage->delete($existing_order_items);
    }
  }

  /**
   * Selects the payment method for the given subscriptions.
   *
   * It is assumed that even if the billing schedule allows multiple
   * subscriptions per recurring order, there will still be a single enforced
   * payment method per customer. In case multiple payment methods are found,
   * the more recent one will be used.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface[] $subscriptions
   *   The subscriptions.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface|null
   *   The payment method, or NULL if none were found.
   */
  protected function selectPaymentMethod(array $subscriptions) {
    $payment_methods = [];
    foreach ($subscriptions as $subscription) {
      if ($payment_method = $subscription->getPaymentMethod()) {
        $payment_methods[$payment_method->id()] = $payment_method;
      }
    }
    ksort($payment_methods, SORT_NUMERIC);
    $payment_method = reset($payment_methods);

    return $payment_method ?: NULL;
  }

  /**
   * Gets the order item type ID for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   The subscription.
   *
   * @return string
   *   The order item type ID.
   */
  protected function getOrderItemTypeId(SubscriptionInterface $subscription) {
    if ($purchasable_entity_type_id = $subscription->getType()->getPurchasableEntityTypeId()) {
      return 'recurring_' . str_replace('commerce_', '', $purchasable_entity_type_id);
    }
    else {
      return 'recurring_standalone';
    }
  }

}
