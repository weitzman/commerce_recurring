<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Entity;

use Drupal\commerce_recurring\Entity\Recurring;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\Store;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the Commerce Recurring entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Entity\Recurring
 *
 * @group commerce_recurring
 */
class RecurringTest extends EntityKernelTestBase {

  /**
   * A sample recurring orders.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $recurringOrders = [];

  /**
   * A sample profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profile;

  /**
   * A sample adjustments.
   *
   * @var \Drupal\commerce_order\EntityAdjustableInterface[]
   */
  protected $recurringAdjustments = [];

  /**
   * A sample order items.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface[]
   */
  protected $recurringOrderItems = [];

  /**
   * A sample store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

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

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_recurring');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_store');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_recurring');

    // Create a store.
    $store = Store::create([
      'type' => 'default',
      'name' => 'Sample store',
      'default_currency' => 'USD',
    ]);
    $store->save();
    $this->store = $this->reloadEntity($store);

    // A order item type that doesn't need a purchasable entity, for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    // Create an user.
    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    // Create a profile.
    $profile = Profile::create([
      'type' => 'billing',
    ]);
    $profile->save();
    $this->profile = $this->reloadEntity($profile);

    // Create two sample order.
    $order_items = [];
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('4.00', 'USD'),
    ]);
    $order_item->save();
    $order_items[0] = $this->reloadEntity($order_item);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('3.00', 'USD'),
    ]);
    $order_item->save();
    $order_items[1] = $this->reloadEntity($order_item);

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->setStore($this->store);
    $order->setCustomer($this->user);
    $order->setEmail('commerce_recurring@example.com');
    $order->setBillingProfile($profile);
    $order->setItems($order_items);
    $order->setCreatedTime(1473069600);
    $order->setPlacedTime(1473069600);
    $order->save();
    $this->recurringOrders[0] = $this->reloadEntity($order);

    $order_items = [];
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('4.00', 'USD'),
    ]);
    $order_item->save();
    $order_items[0] = $this->reloadEntity($order_item);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('3.00', 'USD'),
    ]);
    $order_item->save();
    $order_items[1] = $this->reloadEntity($order_item);

    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
    ]);
    $order->setStore($this->store);
    $order->setCustomer($this->user);
    $order->setEmail('commerce_recurring@example.com');
    $order->setBillingProfile($profile);
    $order->setItems($order_items);
    $order->setCreatedTime(1473069600);
    $order->setPlacedTime(1473069600);
    $order->save();
    $this->recurringOrders[1] = $this->reloadEntity($order);

    // Create two order items for the recurring.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '1',
      'unit_price' => new Price('4.00', 'USD'),
    ]);
    $order_item->save();
    $this->recurringOrderItems[0] = $this->reloadEntity($order_item);

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => '2',
      'unit_price' => new Price('3.00', 'USD'),
    ]);
    $order_item->save();
    $this->recurringOrderItems[1] = $this->reloadEntity($order_item);

    // Create two commerce adjustments for the recurring.
    $adjustments[0] = new Adjustment([
      'type' => 'custom',
      'label' => 'Discount',
      'amount' => new Price('-1.00', 'USD'),
    ]);
    $adjustments[1] = new Adjustment([
      'type' => 'custom',
      'label' => 'Handling fee',
      'amount' => new Price('10.00', 'USD'),
    ]);
    $this->recurringAdjustments = $adjustments;
  }

  /**
   * Tests the recurring entity and its methods.
   *
   * @covers ::getCurrentUserId
   * @covers ::addAdjustment
   * @covers ::removeAdjustment
   * @covers ::getAdjustments
   * @covers ::setAdjustments
   * @covers ::getBillingProfile
   * @covers ::setBillingProfile
   * @covers ::getBillingProfileId
   * @covers ::setBillingProfileId
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getData
   * @covers ::setData
   * @covers ::getDueDateTime
   * @covers ::setDueDateTime
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getEndDateTime
   * @covers ::setEndDateTime
   * @covers ::isEnabled
   * @covers ::setEnabled
   * @covers ::getIntervalTime
   * @covers ::setIntervalTime
   * @covers ::getIpAddress
   * @covers ::setIpAddress
   * @covers ::addOrderItem
   * @covers ::hasOrderItem
   * @covers ::removeOrderItem
   * @covers ::getOrderItems
   * @covers ::hasOrderItems
   * @covers ::setOrderItems
   * @covers ::addRecurringOrder
   * @covers ::hasRecurringOrder
   * @covers ::removeRecurringOrder
   * @covers ::getRecurringOrders
   * @covers ::hasRecurringOrders
   * @covers ::setRecurringOrders
   * @covers ::getOwner
   * @covers ::setOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   * @covers ::getRecurringNumber
   * @covers ::setRecurringNumber
   * @covers ::getStartDateTime
   * @covers ::setStartDateTime
   * @covers ::getStore
   * @covers ::setStore
   * @covers ::getStoreId
   * @covers ::setStoreId
   * @covers ::getTotalPrice
   */
  public function testRecurring() {
    $recurring = Recurring::create([
      'type' => 'default',
    ]);
    $recurring->save();

    // Test public static getCurrentUserId method.
    $this->assertEquals([\Drupal::currentUser()->id()], $recurring->getCurrentUserId());

    // Test public (get|set)BillingProfile methods.
    $recurring->setBillingProfile($this->profile);
    $this->assertEquals($this->profile, $recurring->getBillingProfile());
    $this->assertEquals($this->profile->id(), $recurring->getBillingProfileId());

    // Test public (get|set)BillingProfileId methods.
    $recurring->setBillingProfileId(0);
    $this->assertEquals(NULL, $recurring->getBillingProfile());
    $recurring->setBillingProfileId([$this->profile->id()]);
    $this->assertEquals($this->profile, $recurring->getBillingProfile());
    $this->assertEquals($this->profile->id(), $recurring->getBillingProfileId());

    // Test public (get|set)CreatedTime methods.
    $recurring->setCreatedTime(1473069700);
    $this->assertEquals(1473069700, $recurring->getCreatedTime());

    // Test public (get|set)Data methods.
    $data = [
      'recurring' => [
        'sample' => 'My sample data',
      ],
    ];
    $recurring->setData($data);
    $this->assertEquals($data, $recurring->getData());

    // Test public (get|set)DueDateTime methods.
    $recurring->setDueDateTime(1473069800);
    $this->assertEquals(1473069800, $recurring->getDueDateTime());

    // Test public (get|set)Email methods.
    $recurring->setEmail('commerce_recurring@example.com');
    $this->assertEquals('commerce_recurring@example.com', $recurring->getEmail());

    // Test public (get|set)EndDateTime methods.
    $recurring->setEndDateTime(1473069900);
    $this->assertEquals(1473069900, $recurring->getEndDateTime());

    // Test public (is|set)Enabled methods.
    $recurring->setEnabled(FALSE);
    $this->assertEquals(FALSE, $recurring->isEnabled());
    $recurring->setEnabled(TRUE);
    $this->assertEquals(TRUE, $recurring->isEnabled());

    // Test public (get|set)IntervalTime methods.
    $interval = [
      'interval' => 31,
      'period' => 'day',
    ];
    $recurring->setIntervalTime($interval);
    $this->assertEquals($interval, $recurring->getIntervalTime());

    // Test public get and get IpAddress methods.
    $recurring->setIpAddress('127.0.0.2');
    $this->assertEquals('127.0.0.2', $recurring->getIpAddress());

    // Test public (add|has|remove)OrderItem and (get|has|set)OrderItems methods.
    $recurring->setOrderItems($this->recurringOrderItems);
    $this->assertEquals($this->recurringOrderItems, $recurring->getOrderItems());
    $this->assertTrue($recurring->hasOrderItems());
    $recurring->removeOrderItem($this->recurringOrderItems[1]);
    $this->assertEquals([$this->recurringOrderItems[0]], $recurring->getOrderItems());
    $this->assertTrue($recurring->hasOrderItem($this->recurringOrderItems[0]));
    $this->assertFalse($recurring->hasOrderItem($this->recurringOrderItems[1]));
    $recurring->addOrderItem($this->recurringOrderItems[1]);
    $this->assertEquals($this->recurringOrderItems, $recurring->getOrderItems());
    $this->assertTrue($recurring->hasOrderItem($this->recurringOrderItems[1]));

    // Test public (add|has|remove)RecurringOrder and
    // (get|has|set)RecurringOrders methods.
    $recurring->setRecurringOrders($this->recurringOrders);
    $this->assertEquals($this->recurringOrders, $recurring->getRecurringOrders());
    $this->assertTrue($recurring->hasRecurringOrders());
    $recurring->removeRecurringOrder($this->recurringOrders[1]);
    $this->assertEquals([$this->recurringOrders[0]], $recurring->getRecurringOrders());
    $this->assertTrue($recurring->hasRecurringOrder($this->recurringOrders[0]));
    $this->assertFalse($recurring->hasRecurringOrder($this->recurringOrders[1]));
    $recurring->addRecurringOrder($this->recurringOrders[1]);
    $this->assertEquals($this->recurringOrders, $recurring->getRecurringOrders());
    $this->assertTrue($recurring->hasRecurringOrder($this->recurringOrders[1]));

    // Test public (add|remove)Adjustment and (get|set)Adjustments and
    // getTotalPrice methods.
    $this->assertEquals(new Price('10.00', 'USD'), $recurring->getTotalPrice());
    $recurring->addAdjustment($this->recurringAdjustments[0]);
    $recurring->addAdjustment($this->recurringAdjustments[1]);
    $this->assertEquals($this->recurringAdjustments, $recurring->getAdjustments());
    $recurring->removeAdjustment($this->recurringAdjustments[0]);
    $this->assertEquals([$this->recurringAdjustments[1]], $recurring->getAdjustments());
    $this->assertEquals(new Price('20.00', 'USD'), $recurring->getTotalPrice());
    $recurring->setAdjustments($this->recurringAdjustments);
    $this->assertEquals($this->recurringAdjustments, $recurring->getAdjustments());
    $this->assertEquals(new Price('19.00', 'USD'), $recurring->getTotalPrice());
    // Add an adjustment to the second order item, confirm it's a part of the
    // recurring total, multiplied by quantity.
    $recurring->removeOrderItem($this->recurringOrderItems[1]);
    $order_item = $this->recurringOrderItems[1];
    $order_item->addAdjustment(new Adjustment([
      'type' => 'custom',
      'label' => 'Random fee',
      'amount' => new Price('5.00', 'USD'),
    ]));
    $recurring->addOrderItem($order_item);
    $this->assertEquals(new Price('29.00', 'USD'), $recurring->getTotalPrice());

    // Test public (get|set)Owner methods.
    $recurring->setOwner($this->user);
    $this->assertEquals($this->user, $recurring->getOwner());
    $this->assertEquals($this->user->id(), $recurring->getOwnerId());

    // Test public (get|set)OwnerId methods.
    $recurring->setOwnerId(0);
    $this->assertEquals(NULL, $recurring->getOwner());
    $recurring->setOwnerId($this->user->id());
    $this->assertEquals($this->user, $recurring->getOwner());
    $this->assertEquals($this->user->id(), $recurring->getOwnerId());

    // Test public (get|set)RecurringNumber methods.
    $recurring->setRecurringNumber(7);
    $this->assertEquals(7, $recurring->getRecurringNumber());

    // Test public (get|set)StartDateTime methods.
    $recurring->setStartDateTime(1473070000);
    $this->assertEquals(1473070000, $recurring->getStartDateTime());

    // Test public (get|set)Store methods.
    $recurring->setStore($this->store);
    $this->assertEquals($this->store, $recurring->getStore());
    $this->assertEquals($this->store->id(), $recurring->getStoreId());

    // Test public (get|set)StoreId methods.
    $recurring->setStoreId(0);
    $this->assertEquals(NULL, $recurring->getStore());
    $recurring->setStoreId([$this->store->id()]);
    $this->assertEquals($this->store, $recurring->getStore());
    $this->assertEquals($this->store->id(), $recurring->getStoreId());
  }

}
