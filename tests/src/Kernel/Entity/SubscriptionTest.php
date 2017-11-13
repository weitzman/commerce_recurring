<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Entity;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the subscription entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Entity\Subscription
 *
 * @group commerce_recurring
 */
class SubscriptionTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_product',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_recurring',
    'commerce_recurring_test',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_subscription');

    ProductVariationType::create([
      'id' => 'default',
      'label' => 'Default',
    ])->save();
  }

  /**
   * @covers ::getType
   * @covers ::getStore
   * @covers ::getStoreId
   * @covers ::getBillingSchedule
   * @covers ::setBillingSchedule
   * @covers ::getCustomer
   * @covers ::setCustomer
   * @covers ::getCustomerId
   * @covers ::setCustomerId
   * @covers ::getPaymentMethod
   * @covers ::setPaymentMethod
   * @covers ::getPaymentMethodId
   * @covers ::getTitle
   * @covers ::setTitle
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getUnitPrice
   * @covers ::setUnitPrice
   * @covers ::getState
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getRenewedTime
   * @covers ::setRenewedTime
   * @covers ::getStartTime
   * @covers ::setStartTime
   * @covers ::getEndTime
   * @covers ::setEndTime
   */
  public function testSubscription() {
    $billing_schedule = BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Test label',
      'displayLabel' => 'Test customer label',
      'plugin' => 'test_plugin',
      'configuration' => [
        'key' => 'value',
      ],
    ]);
    $billing_schedule->save();
    $billing_schedule = $this->reloadEntity($billing_schedule);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $payment_gateway,
    ]);
    $payment_method->save();
    $payment_method = $this->reloadEntity($payment_method);

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
    ]);
    $variation->save();
    $variation = $this->reloadEntity($variation);

    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $billing_schedule,
      'uid' => 0,
      'payment_method' => $payment_method,
      'title' => 'My subscription',
      'purchased_entity' => $variation,
      'quantity' => 2,
      'unit_price' => new Price('2', 'USD'),
      'state' => 'pending',
      'created' => 1507642328,
      'starts' => 1507642328 + 10,
      'ends' => 1507642328 + 50,
    ]);
    $subscription->save();

    $subscription = Subscription::load($subscription->id());
    $this->assertInstanceOf(SubscriptionTypeInterface::class, $subscription->getType());
    $this->assertEquals('product_variation', $subscription->getType()->getPluginId());
    $this->assertEquals($this->store, $subscription->getStore());
    $this->assertEquals($this->store->id(), $subscription->getStoreId());

    $this->assertEquals($billing_schedule, $subscription->getBillingSchedule());

    $this->assertEquals($payment_method, $subscription->getPaymentMethod());
    $this->assertEquals($payment_method->id(), $subscription->getPaymentMethodId());

    $this->assertTrue($subscription->hasPurchasedEntity());
    $this->assertEquals($variation, $subscription->getPurchasedEntity());
    $this->assertEquals($variation->id(), $subscription->getPurchasedEntityId());

    $this->assertEquals('My subscription', $subscription->getTitle());
    $subscription->setTitle('My premium subscription');
    $this->assertEquals('My premium subscription', $subscription->getTitle());

    $this->assertEquals('2', $subscription->getQuantity());
    $subscription->setQuantity('3');
    $this->assertEquals('3', $subscription->getQuantity());

    $this->assertEquals(new Price('2', 'USD'), $subscription->getUnitPrice());
    $subscription->setUnitPrice(new Price('3', 'USD'));
    $this->assertEquals(new Price('3', 'USD'), $subscription->getUnitPrice());

    $this->assertEquals('pending', $subscription->getState()->value);

    $this->assertEquals(1507642328, $subscription->getCreatedTime());
    $subscription->setCreatedTime(1508002101);
    $this->assertEquals(1508002101, $subscription->getCreatedTime());

    $this->assertEquals(0, $subscription->getRenewedTime());
    $subscription->setRenewedTime(123456);
    $this->assertEquals(123456, $subscription->getRenewedTime());

    $this->assertEquals(1507642328 + 10, $subscription->getStartTime());
    $subscription->setStartTime(1508002120);
    $this->assertEquals(1508002120, $subscription->getStartTime());

    $this->assertEquals(1507642328 + 50, $subscription->getEndTime());
    $subscription->setEndTime(1508002920);
    $this->assertEquals(1508002920, $subscription->getEndTime());
  }

}
