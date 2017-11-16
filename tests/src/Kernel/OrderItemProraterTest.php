<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\OrderItemProrater
 * @group commerce_recurring
 */
class OrderItemProraterTest extends RecurringKernelTestBase {

  /**
   * The order item prorater.
   *
   * @var \Drupal\commerce_recurring\OrderItemProraterInterface
   */
  protected $orderItemProrater;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->orderItemProrater = $this->container->get('commerce_recurring.order_item_prorater');
  }

  /**
   * @covers ::prorateInitial
   */
  public function testProrateInitial() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $this->variation->getOrderItemTitle(),
      'purchased_entity' => $this->variation->id(),
      'unit_price' => new Price('30', 'USD'),
    ]);
    $order_item->save();

    // Full hour, full price.
    $start_date = new DrupalDateTime('2017-06-01 17:00:00');
    $prorated_unit_price = $this->orderItemProrater->prorateInitial($order_item, $this->billingSchedule, $start_date);
    $this->assertEquals(new Price('30', 'USD'), $prorated_unit_price);

    // Half-hour, half price.
    $start_date = new DrupalDateTime('2017-06-01 17:30:00');
    $prorated_unit_price = $this->orderItemProrater->prorateInitial($order_item, $this->billingSchedule, $start_date);
    $this->assertEquals(new Price('15', 'USD'), $prorated_unit_price);

    // Confirm that the prorated amounts are rounded.
    $start_date = new DrupalDateTime('2017-06-01 17:35:47');
    $prorated_unit_price = $this->orderItemProrater->prorateInitial($order_item, $this->billingSchedule, $start_date);
    $this->assertEquals(new Price('12.11', 'USD'), $prorated_unit_price);
  }

  /**
   * @covers ::prorateRecurring
   */
  public function testProrateRecurring() {
    $billing_period = new BillingPeriod(
      new DrupalDateTime('2017-06-01 00:00:00'),
      new DrupalDateTime('2017-07-01 00:00:00')
    );
    $order_item = OrderItem::create([
      'type' => 'recurring_standalone',
      'title' => 'My subscription',
      'unit_price' => new Price('30', 'USD'),
      'billing_period' => $billing_period,
    ]);
    $order_item->save();

    // Full month, full price.
    $prorated_unit_price = $this->orderItemProrater->prorateRecurring($order_item, $billing_period);
    $this->assertEquals(new Price('30', 'USD'), $prorated_unit_price);

    // Half-month, half price.
    $partial_billing_period = new BillingPeriod(
      new DrupalDateTime('2017-06-01 00:00:00'),
      new DrupalDateTime('2017-06-16 00:00:00')
    );
    $order_item->set('billing_period', $partial_billing_period);
    $order_item->save();
    $prorated_unit_price = $this->orderItemProrater->prorateRecurring($order_item, $billing_period);
    $this->assertEquals(new Price('15', 'USD'), $prorated_unit_price);
  }

}
