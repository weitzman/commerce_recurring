<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides the default order item prorater.
 */
class OrderItemProrater implements OrderItemProraterInterface {

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new OrderItemProrater object.
   *
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   */
  public function __construct(RounderInterface $rounder) {
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public function prorateInitial(OrderItemInterface $order_item, BillingScheduleInterface $billing_schedule, DrupalDateTime $start_date = NULL) {
    $start_date = $start_date ?: new DrupalDateTime();
    $billing_period = $billing_schedule->getPlugin()->generateFirstBillingPeriod($start_date);
    $partial_billing_period = new BillingPeriod($start_date, $billing_period->getEndDate());

    return $this->proratePrice($order_item->getUnitPrice(), $partial_billing_period, $billing_period);
  }

  /**
   * {@inheritdoc}
   */
  public function prorateRecurring(OrderItemInterface $order_item, BillingPeriod $billing_period) {
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $partial_billing_period_item */
    $partial_billing_period_item = $order_item->get('billing_period')->first();
    $partial_billing_period = $partial_billing_period_item->toBillingPeriod();

    return $this->proratePrice($order_item->getUnitPrice(), $partial_billing_period, $billing_period);
  }

  /**
   * Prorates the given price for a partial billing period.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   * @param \Drupal\commerce_recurring\BillingPeriod $partial_billing_period
   *   The partial billing period.
   * @param \Drupal\commerce_recurring\BillingPeriod $billing_period
   *   The billing period.
   *
   * @return \Drupal\commerce_price\Price
   *   The prorated price.
   */
  protected function proratePrice(Price $price, BillingPeriod $partial_billing_period, BillingPeriod $billing_period) {
    $duration = $billing_period->getDuration();
    $partial_duration = $partial_billing_period->getDuration();
    if ($duration != $partial_duration) {
      $price = $price->multiply(Calculator::divide($partial_duration, $duration));
      $price = $this->rounder->round($price);
    }

    return $price;
  }

}
