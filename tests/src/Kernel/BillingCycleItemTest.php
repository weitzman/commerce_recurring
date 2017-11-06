<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the billing cycle field type.
 *
 * @group commerce_recurring
 */
class BillingCycleItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_recurring',
    'field',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_billing_cycle',
      'type' => 'commerce_billing_cycle',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_billing_cycle',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests the field.
   */
  public function testField() {
    $start_date = new DrupalDateTime('2017-10-19 15:07:12');
    $end_date = new DrupalDateTime('2017-11-19 15:07:12');

    $entity = EntityTest::create([
      'field_billing_cycle' => [
        'starts' => $start_date->format('U'),
        'ends' => $end_date->format('U'),
      ],
    ]);
    $entity->save();

    $entity = EntityTest::load($entity->id());
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_field */
    $billing_cycle_field = $entity->get('field_billing_cycle')->first();
    $this->assertEquals($start_date->format('U'), $billing_cycle_field->starts);
    $this->assertEquals($end_date->format('U'), $billing_cycle_field->ends);

    $billing_cycle = $billing_cycle_field->toBillingCycle();
    $this->assertInstanceOf(BillingCycle::class, $billing_cycle);
    $this->assertEquals($start_date, $billing_cycle->getStartDate());
    $this->assertEquals($end_date, $billing_cycle->getEndDate());

    // Test passing billing cycles.
    $new_end_date = new DrupalDateTime('2017-12-19 15:07:12');
    $billing_cycle = new BillingCycle($end_date, $new_end_date);
    $entity->set('field_billing_cycle', $billing_cycle);
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $billing_cycle_field */
    $billing_cycle_field = $entity->get('field_billing_cycle')->first();

    $returned_billing_cycle = $billing_cycle_field->toBillingCycle();
    $this->assertEquals($billing_cycle, $returned_billing_cycle);
  }

}
