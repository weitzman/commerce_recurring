<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Entity;

use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\commerce_recurring_test\Plugin\Commerce\BillingSchedule\TestPlugin;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the billing schedule entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Entity\BillingSchedule
 *
 * @group commerce_recurring
 */
class BillingScheduleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_recurring',
    'commerce_recurring_test',
  ];

  /**
   * @covers ::id
   * @covers ::label
   * @covers ::getDisplayLabel
   * @covers ::getBillingType
   * @covers ::getPlugin
   * @covers ::getPluginId
   * @covers ::getPluginConfiguration
   * @covers ::setPluginConfiguration
   */
  public function testBillingSchedule() {
    BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Test label',
      'displayLabel' => 'Test customer label',
      'billingType' => BillingScheduleInterface::BILLING_TYPE_POSTPAID,
      'plugin' => 'test_plugin',
      'configuration' => [
        'key' => 'value',
      ],
    ])->save();

    $billing_schedule = BillingSchedule::load('test_id');
    $this->assertEquals('test_id', $billing_schedule->id());
    $this->assertEquals('Test label', $billing_schedule->label());
    $this->assertEquals('Test customer label', $billing_schedule->getDisplayLabel());
    $this->assertEquals(BillingScheduleInterface::BILLING_TYPE_POSTPAID, $billing_schedule->getBillingType());

    $this->assertEquals('test_plugin', $billing_schedule->getPluginId());
    $this->assertEquals(['key' => 'value'], $billing_schedule->getPluginConfiguration());
    $billing_schedule->setPluginConfiguration(['key' => 'value2']);
    $this->assertEquals(['key' => 'value2'], $billing_schedule->getPluginConfiguration());

    $plugin = $billing_schedule->getPlugin();
    $this->assertInstanceOf(TestPlugin::class, $plugin);
    $this->assertEquals($billing_schedule->getPluginId(), $plugin->getPluginId());
    $this->assertEquals($billing_schedule->getPluginConfiguration(), $plugin->getConfiguration());
  }

}
