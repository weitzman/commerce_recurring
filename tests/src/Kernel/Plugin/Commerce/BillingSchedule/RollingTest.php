<?php

namespace Drupal\Tests\commerce_recurring\Plugin\Commerce\BillingSchedule;

use Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Rolling;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the rolling billing schedule.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Plugin\Commerce\BillingSchedule\Rolling
 * @group commerce_recurring
 */
class RollingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_recurring'];

  /**
   * @covers ::generateFirstBillingCycle
   * @covers ::generateNextBillingCycle
   */
  public function testGenerate() {
    $plugin = new Rolling([
      'number' => '2',
      'unit' => 'hour',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_cycle = $plugin->generateFirstBillingCycle($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:22:30'), $billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:22:30'), $billing_cycle->getEndDate());
    $next_billing_cycle = $plugin->generateNextBillingCycle($start_date, $billing_cycle);
    $this->assertEquals(new DrupalDateTime('2017-03-16 12:22:30'), $next_billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-16 14:22:30'), $next_billing_cycle->getEndDate());

    $plugin = new Rolling([
      'number' => '1',
      'unit' => 'month',
    ], '', []);
    $start_date = new DrupalDateTime('2017-01-30 10:22:30');
    $billing_cycle = $plugin->generateFirstBillingCycle($start_date);
    $this->assertEquals(new DrupalDateTime('2017-01-30 10:22:30'), $billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-02-28 10:22:30'), $billing_cycle->getEndDate());
    $next_billing_cycle = $plugin->generateNextBillingCycle($start_date, $billing_cycle);
    $this->assertEquals(new DrupalDateTime('2017-02-28 10:22:30'), $next_billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2017-03-30 10:22:30'), $next_billing_cycle->getEndDate());

    $plugin = new Rolling([
      'number' => '1',
      'unit' => 'year',
    ], '', []);
    $start_date = new DrupalDateTime('2017-03-16 10:22:30');
    $billing_cycle = $plugin->generateFirstBillingCycle($start_date);
    $this->assertEquals(new DrupalDateTime('2017-03-16 10:22:30'), $billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2018-03-16 10:22:30'), $billing_cycle->getEndDate());
    $next_billing_cycle = $plugin->generateNextBillingCycle($start_date, $billing_cycle);
    $this->assertEquals(new DrupalDateTime('2018-03-16 10:22:30'), $next_billing_cycle->getStartDate());
    $this->assertEquals(new DrupalDateTime('2019-03-16 10:22:30'), $next_billing_cycle->getEndDate());
  }

}
