<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Provides a base class for Recurring kernel tests.
 */
class RecurringKernelTestBase extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'action',
    'advancedqueue',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_product',
    'commerce_recurring',
    'commerce_recurring_test',
    'entity_reference_revisions',
  ];

  /**
   * The test billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $billingSchedule;

  /**
   * The test payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * The test payment method.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * The test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_subscription');
    $this->installEntitySchema('user');
    $this->installSchema('advancedqueue', 'advancedqueue');
    $this->installConfig('entity');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_recurring');

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = BillingSchedule::create([
      'id' => 'test_id',
      'label' => 'Test label',
      'displayLabel' => 'Test customer label',
      'billingType' => BillingSchedule::BILLING_TYPE_POSTPAID,
      'plugin' => 'test_plugin',
      'configuration' => [
        'key' => 'value',
      ],
      'dunningSchedule' => [0, 3, 7, 11],
      'dunningDisposition' => 'cancel',
    ]);
    $billing_schedule->save();
    $this->billingSchedule = $this->reloadEntity($billing_schedule);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'example',
      'label' => 'Example',
      'plugin' => 'example_onsite',
    ]);
    $payment_gateway->save();
    $this->paymentGateway = $this->reloadEntity($payment_gateway);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->paymentGateway,
      'uid' => $this->user->id(),
    ]);
    $payment_method->save();
    $this->paymentMethod = $this->reloadEntity($payment_method);

    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $product_variation_type */
    $product_variation_type = ProductVariationType::load('default');
    $product_variation_type->setGenerateTitle(FALSE);
    $product_variation_type->save();
    // Install the variation trait.
    $trait_manager = \Drupal::service('plugin.manager.commerce_entity_trait');
    $trait = $trait_manager->createInstance('purchasable_entity_subscription');
    $trait_manager->installTrait($trait, 'commerce_product_variation', 'default');

    $variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'billing_schedule' => $this->billingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'product_variation',
      ],
    ]);
    $variation->save();
    $this->variation = $this->reloadEntity($variation);
  }

  /**
   * Changes the current time.
   *
   * @param int $new_time
   *   The new time.
   */
  protected function rewindTime($new_time) {
    $mock_time = $this->prophesize(TimeInterface::class);
    $mock_time->getCurrentTime()->willReturn($new_time);
    $mock_time->getRequestTime()->willReturn($new_time);
    $this->container->set('datetime.time', $mock_time->reveal());
  }

}
