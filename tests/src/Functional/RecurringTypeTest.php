<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\commerce_recurring\Entity\RecurringType;

/**
 * Tests the commerce_recurring_type entity forms.
 *
 * @group commerce_recurring
 */
class RecurringTypeTest extends CommerceRecurringBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a sample store.
    $this->createSampleStore();
  }

  /**
   * Tests if the default recurring type was created.
   */
  public function testDefaultRecurringType() {
    $recurring_types = RecurringType::loadMultiple();
    $this->assertTrue(isset($recurring_types['default']), 'Default recurring type is available');

    $recurring_type = RecurringType::load('default');
    $this->assertEquals($recurring_types['default'], $recurring_type, 'The correct recurring type is loaded');
  }

  /**
   * Tests creating a recurring type programmatically and through the add form.
   */
  public function testCreateRecurringType() {
    // Create a recurring type programmatically.
    $type = $this->createEntity('commerce_recurring_type', [
      'id' => 'sample',
      'label' => 'Label of sample',
    ]);

    $type_exists = (bool) RecurringType::load($type->id());
    $this->assertTrue($type_exists, 'The new recurring type has been created in the database.');

    // Create a recurring type through the add form.
    $this->drupalGet('/admin/commerce/config/recurring-types');
    $this->getSession()->getPage()->clickLink('Add a new recurring type');

    $values = [
      'id' => 'sample2',
      'label' => 'Label of sample2',
    ];
    $this->submitForm($values, t('Save'));

    $type_exists = (bool) RecurringType::load($values['id']);
    $this->assertTrue($type_exists, 'The new recurring type has been created in the database.');
  }

  /**
   * Tests deleting a recurring type programmatically and through the form.
   */
  public function testDeleteRecurringType() {
    // Create a recurring type programmatically.
    $type = $this->createEntity('commerce_recurring_type', [
      'id' => 'sample2',
      'label' => 'Label for sample2',
    ]);
    commerce_recurring_add_line_items_field($type);
    commerce_recurring_add_recurring_orders_field($type);

    // Create a recurring.
    $recurring = $this->createEntity('commerce_recurring', [
      'type' => $type->id(),
      'mail' => $this->loggedInUser->getEmail(),
    ]);

    // Try to delete the recurring type.
    $this->drupalGet('admin/commerce/config/recurring-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('@type is used by 1 recurring on your site. You can not remove this recurring type until you have removed all of the @type recurrings.', ['@type' => $type->label()]));
    $this->assertSession()->pageTextNotContains(t('This action cannot be undone.'));

    // Deleting the recurring type when its not being referenced by a recurring.
    $recurring->delete();
    $this->drupalGet('admin/commerce/config/recurring-types/' . $type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the recurring type @label?', ['@label' => $type->label()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], t('Delete'));
    $type_exists = (bool) RecurringType::load($type->id());
    $this->assertFalse($type_exists, 'The recurring type has been deleted from the database.');
  }

}
