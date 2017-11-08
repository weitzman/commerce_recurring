<?php

namespace Drupal\commerce_recurring\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.pre_transition'] = 'onPlaceTransition';
    return $events;
  }

  /**
   * Creates subscriptions when the initial order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPlaceTransition(WorkflowTransitionEvent $event) {
    $subscription_storage = $this->entityTypeManager->getStorage('commerce_subscription');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $payment_method = $order->get('payment_method')->entity;
    if (empty($payment_method)) {
      return;
    }

    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || !$purchased_entity->hasField('subscription_type')) {
        return;
      }
      $subscription_type_item = $purchased_entity->get('subscription_type');
      $billing_schedule_item = $purchased_entity->get('billing_schedule');
      if ($subscription_type_item->isEmpty() || $billing_schedule_item->isEmpty()) {
        return;
      }

      $subscription = $subscription_storage->create([
        'type' => $subscription_type_item->target_plugin_id,
        'billing_schedule' => $billing_schedule_item->entity,
        'uid' => $order->getCustomerId(),
        'payment_method' => $payment_method,
        'purchased_entity' => $purchased_entity,
        'amount' => $order_item->getUnitPrice(),
        'state' => 'active',
      ]);
      $subscription->save();
    }
  }

}
