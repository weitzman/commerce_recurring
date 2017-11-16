<?php

namespace Drupal\Tests\commerce_recurring;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\BillingPeriod
 * @group commerce_recurring
 */
class BillingPeriodTest extends KernelTestBase {

  /**
   * @covers ::__construct
   * @covers ::getStartDate
   * @covers ::getEndDate
   * @covers ::getDuration
   */
  public function testBillingPeriod() {
    $start_date = new DrupalDateTime('2017-01-01 00:00:00');
    $end_date = new DrupalDateTime('2017-01-02 00:00:00');
    $billing_period = new BillingPeriod($start_date, $end_date);

    $this->assertEquals($start_date, $billing_period->getStartDate());
    $this->assertEquals($end_date, $billing_period->getEndDate());
    $this->assertEquals(86400, $billing_period->getDuration());
  }

}
