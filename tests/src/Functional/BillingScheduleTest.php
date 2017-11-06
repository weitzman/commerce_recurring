<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests billing schedules.
 */
class BillingScheduleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_recurring',
    'commerce_recurring_test',
    'block',
    'commerce_product',
  ];

  public function testCrudUiTest() {
    $admin_user = $this->drupalCreateUser(['administer commerce_billing_schedule']);
    $this->drupalLogin($admin_user);
    $this->placeBlock('local_actions_block');

    $this->drupalGet('admin/commerce/config/billing-schedule');
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('Add billing schedule');
    $this->submitForm([
      'label' => 'My admin label',
      'id' => 'test_id',
      'displayLabel' => 'My display label',
      'plugin' => 'test_plugin',
    ], 'Save');
    $this->clickLink('Edit');
    $this->submitForm([
      'configuration[test_plugin][key]' => 'value1',
    ], 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/billing-schedule');
    $this->assertSession()->pageTextContains('Saved the My admin label billing schedule.');

    // 2. Ensure the entity is listed
    $this->assertSession()->pageTextContains('My admin label');

    // 3. Edit the entity
    $this->clickLink('Edit');
    $this->assertSession()->fieldValueEquals('configuration[test_plugin][key]', 'value1');
    $this->submitForm([
      'configuration[test_plugin][key]' => 'value2',
    ], 'Save');
    $this->assertSession()->addressEquals('admin/commerce/config/billing-schedule');
    $this->clickLink('Edit');
    $this->assertSession()->fieldValueEquals('configuration[test_plugin][key]', 'value2');
    $this->submitForm([], 'Save');

    // 4. Delete the entity
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');

    $this->assertSession()->pageTextNotContains('test_id');
  }

}
