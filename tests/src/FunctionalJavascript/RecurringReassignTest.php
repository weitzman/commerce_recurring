<?php

namespace Drupal\Tests\commerce_recurring\FunctionalJavascript;

use Drupal\commerce_recurring\Entity\Recurring;
use Drupal\Tests\commerce_recurring\Functional\CommerceRecurringBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the commerce_recurring reassign form.
 *
 * @group commerce_recurring
 */
class RecurringReassignTest extends CommerceRecurringBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a sample store.
    $this->createSampleStore();
  }

  /**
   * Tests the reassign form with a new user.
   */
  public function testRecurringReassign() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'product_variation',
      'unit_price' => [
        'amount' => '100',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring */
    $recurring = $this->createEntity('commerce_recurring', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'uid' => $this->loggedInUser->id(),
      'order_items' => [$order_item],
    ]);

    $this->assertTrue($recurring->hasLinkTemplate('reassign-form'));

    $this->drupalGet($recurring->toUrl('reassign-form'));
    $this->getSession()->getPage()->fillField('customer_type', 'new');
    $this->waitForAjaxToFinish();

    $values = [
      'mail' => 'commerce_recurring@example.com',
    ];
    $this->submitForm($values, 'Reassign recurring');

    $this->assertEquals($recurring->toUrl('collection', ['absolute' => TRUE])->toString(), $this->getSession()->getCurrentUrl());

    // Reload the recurring.
    \Drupal::service('entity_type.manager')->getStorage('commerce_recurring')->resetCache([$recurring->id()]);
    $recurring = Recurring::load($recurring->id());
    $this->assertEquals($recurring->getOwner()->getEmail(), 'commerce_recurring@example.com');
    $this->assertEquals($recurring->getEmail(), 'commerce_recurring@example.com');
  }

}
