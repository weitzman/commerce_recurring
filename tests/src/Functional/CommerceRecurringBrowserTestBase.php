<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Provides a base class for Commerce Recurring functional tests.
 */
abstract class CommerceRecurringBrowserTestBase extends CommerceBrowserTestBase {

  use StoreCreationTrait;

  /**
   * The store to test against.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'path',
    'entity',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'interval',
    'commerce_recurring',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Create a sample store.
   */
  protected function createSampleStore() {
    $this->store = $this->createStore('Sample store', 'commerce_recurring_store@example.com', 'default', TRUE, 'US', 'USD');
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer orders',
      'administer order types',
      'administer order item types',
      'administer recurrings',
      'administer recurring types',
    ], parent::getAdministratorPermissions());
  }

}
