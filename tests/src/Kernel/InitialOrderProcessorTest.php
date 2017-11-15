<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\InitialOrderProcessor
 * @group commerce_recurring
 */
class InitialOrderProcessorTest extends RecurringKernelTestBase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $this->variation->getOrderItemTitle(),
      'purchased_entity' => $this->variation->id(),
      'unit_price' => $this->variation->getPrice(),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'uid' => $this->user->id(),
      'order_items' => [$order_item],
      'state' => 'draft',
    ]);
    $order->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->reloadEntity($order);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->reloadEntity($order_item);

    $this->assertEquals($this->variation->getPrice(), $order_item->getUnitPrice());
    $this->assertTrue($order_item->getAdjustedUnitPrice()->isZero());

    $this->assertEquals($this->variation->getPrice(), $order->getSubtotalPrice());
    $this->assertTrue($order->getTotalPrice()->isZero());
  }

}
