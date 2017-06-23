<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\commerce_recurring\Entity\Recurring;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\LineItem;

/**
 * Tests the commerce_recurring entity forms.
 *
 * @group commerce_recurring
 */
class RecurringTest extends CommerceRecurringBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a sample store.
    $this->createSampleStore();
  }

  /**
   * Tests creating a recurring programmatically and through the UI.
   */
  public function testCreateRecurring() {
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $order_line_item */
    $order_line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$order_line_item],
    ]);
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $recurring_line_item */
    $recurring_line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring */
    $recurring = $this->createEntity('commerce_recurring', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$recurring_line_item],
      'recurring_orders' => [$order],
    ]);

    $recurring_exists = (bool) Recurring::load($recurring->id());
    $this->assertTrue($recurring_exists, 'The new recurring has been created in the database.');
    $this->assertEquals($recurring->id(), $recurring->getRecurringNumber(), 'The recurring number matches the recurring ID');

    $recurring_line_item_exists = (bool) LineItem::load($recurring_line_item->id());
    $this->assertTrue($recurring_line_item_exists, 'The matching recurring line item has been created in the database.');

    $order_exists = (bool) Order::load($order->id());
    $this->assertTrue($order_exists, 'The new order has been created in the database.');

    $order_line_item_exists = (bool) LineItem::load($order_line_item->id());
    $this->assertTrue($order_line_item_exists, 'The matching order line item has been created in the database.');
  }

  /**
   * Tests deleting a recurring programmatically and through the UI.
   */
  public function testDeleteRecurring() {
    $order_line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$order_line_item],
    ]);
    $recurring_line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring */
    $recurring = $this->createEntity('commerce_recurring', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$recurring_line_item],
      'recurring_orders' => [$order],
    ]);
    $recurring->delete();

    $recurring_exists = (bool) Order::load($recurring->id());
    $this->assertFalse($recurring_exists, 'The new recurring has been deleted from the database.');

    $recurring_line_item_exists = (bool) LineItem::load($recurring_line_item->id());
    $this->assertFalse($recurring_line_item_exists, 'The matching recurring line item has been deleted from the database.');

    $order_exists = (bool) Order::load($order->id());
    $this->assertFalse($order_exists, 'The new order has been deleted from the database.');

    $order_line_item_exists = (bool) LineItem::load($order_line_item->id());
    $this->assertFalse($order_line_item_exists, 'The matching order line item has been deleted from the database.');
  }

}
