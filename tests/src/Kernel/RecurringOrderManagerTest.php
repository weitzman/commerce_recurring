<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\RecurringOrderManager
 * @group commerce_recurring
 */
class RecurringOrderManagerTest extends RecurringKernelTestBase {

  /**
   * A test subscription.
   *
   * @var \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'quantity' => '2',
      'unit_price' => new Price('2', 'USD'),
      'state' => 'pending',
      'starts' => \Drupal::time()->getRequestTime() - 5,
      'ends' => \Drupal::time()->getRequestTime() + 1000,
    ]);
    $subscription->save();
    $this->subscription = $this->reloadEntity($subscription);

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
  }

  /**
   * @covers ::ensureOrder
   */
  public function testEnsureOrder() {
    $order = $this->recurringOrderManager->ensureOrder($this->subscription);
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_item */
    $billing_cycle_item = $order->get('billing_cycle')->first();
    $billing_cycle = $billing_cycle_item->toBillingCycle();

    $this->assertEmpty($this->subscription->getRenewedTime());
    $this->assertEquals('recurring', $order->bundle());
    $this->assertEquals($this->subscription->getStoreId(), $order->getStoreId());
    $this->assertEquals($this->user->id(), $order->getCustomerId());
    $this->assertEquals($this->billingSchedule->id(), $order->get('billing_schedule')->target_id);
    $this->assertEquals('draft', $order->getState()->value);

    $this->assertTrue($order->hasItems());
    $order_items = $order->getItems();
    $order_item = reset($order_items);
    $this->assertEquals('recurring_product_variation', $order_item->bundle());
    $this->assertEquals($this->subscription->getTitle(), $order_item->getTitle());
    $this->assertEquals($this->subscription->getQuantity(), $order_item->getQuantity());
    $this->assertEquals($this->subscription->getUnitPrice(), $order_item->getUnitPrice());
    $this->assertEquals($this->variation, $order_item->getPurchasedEntity());
    $this->assertEquals($billing_cycle->getStartDate()->format('U'), $order_item->get('starts')->value);
    $this->assertEquals($billing_cycle->getEndDate()->format('U'), $order_item->get('ends')->value);
    $this->assertEquals(50, $order_item->get('ends')->value - $order_item->get('starts')->value);
  }

  /**
   * @covers ::renewOrder
   */
  public function testCloseOrderWithoutPaymentMethod() {
    $this->subscription->set('payment_method', NULL);
    $this->subscription->save();

    $this->setExpectedException(HardDeclineException::class, 'Payment method not found.');
    $order = $this->recurringOrderManager->ensureOrder($this->subscription);
    $this->recurringOrderManager->closeOrder($order);
  }

  /**
   * @covers ::closeOrder
   */
  public function testCloseOrder() {
    $order = $this->recurringOrderManager->ensureOrder($this->subscription);
    $this->recurringOrderManager->closeOrder($order);

    $this->assertEquals('completed', $order->getState()->value);
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->container->get('entity_type.manager')->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);
    $this->assertCount(1, $payments);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = reset($payments);
    $this->assertEquals($this->paymentGateway->id(), $payment->getPaymentGatewayId());
    $this->assertEquals($this->paymentMethod->id(), $payment->getPaymentMethodId());
    $this->assertEquals($order->id(), $payment->getOrderId());
    $this->assertEquals($order->getTotalPrice(), $payment->getAmount());
    $this->assertEquals('completed', $payment->getState()->value);
  }

  /**
   * @covers ::renewOrder
   */
  public function testRenewOrder() {
    $order = $this->recurringOrderManager->ensureOrder($this->subscription);
    $next_order = $this->recurringOrderManager->renewOrder($order);
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_item */
    $billing_cycle_item = $order->get('billing_cycle')->first();
    $billing_cycle = $billing_cycle_item->toBillingCycle();
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $next_billing_cycle_item */
    $next_billing_cycle_item = $next_order->get('billing_cycle')->first();
    $next_billing_cycle = $next_billing_cycle_item->toBillingCycle();

    $this->subscription = $this->reloadEntity($this->subscription);
    $this->assertNotEmpty($this->subscription->getRenewedTime());

    $this->assertEquals($billing_cycle->getEndDate(), $next_billing_cycle->getStartDate());
    $this->assertEquals($order->bundle(), $next_order->bundle());
    $this->assertEquals($order->getStoreId(), $next_order->getStoreId());
    $this->assertEquals($order->getCustomerId(), $next_order->getCustomerId());
    $this->assertEquals($order->get('billing_schedule')->target_id, $next_order->get('billing_schedule')->target_id);
    $this->assertEquals('draft', $next_order->getState()->value);

    $this->assertTrue($next_order->hasItems());
    $order_items = $next_order->getItems();
    $order_item = reset($order_items);
    $this->assertEquals('recurring_product_variation', $order_item->bundle());
    $this->assertEquals($this->subscription->getTitle(), $order_item->getTitle());
    $this->assertEquals($this->subscription->getQuantity(), $order_item->getQuantity());
    $this->assertEquals($this->subscription->getUnitPrice(), $order_item->getUnitPrice());
    $this->assertEquals($this->variation, $order_item->getPurchasedEntity());
    $this->assertEquals($next_billing_cycle->getStartDate()->format('U'), $order_item->get('starts')->value);
    $this->assertEquals($next_billing_cycle->getEndDate()->format('U'), $order_item->get('ends')->value);
    $this->assertEquals(50, $order_item->get('ends')->value - $order_item->get('starts')->value);
  }

}
