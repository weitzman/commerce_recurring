<?php

namespace Drupal\Tests\commerce_recurring;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Charge;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\commerce_recurring\Charge
 * @group commerce_recurring
 */
class ChargeTest extends KernelTestBase {

  /**
   * @covers ::__construct
   */
  public function testMissingProperty() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Missing required property "start_date".');
    $charge = new Charge([
      'title' => 'My subscription',
      'unit_price' => new Price('99.99', 'USD'),
    ]);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidPurchasedEntity() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The "purchased_entity" property must be an instance of Drupal\commerce\PurchasableEntityInterface.');
    $charge = new Charge([
      'purchased_entity' => 'INVALID',
      'title' => 'My subscription',
      'unit_price' => new Price('99.99', 'USD'),
      'start_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-01'),
      'end_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-31'),
    ]);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidUnitPrice() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The "unit_price" property must be an instance of Drupal\commerce_price\Price.');
    $charge = new Charge([
      'title' => 'My subscription',
      'unit_price' => 'INVALID',
      'start_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-01'),
      'end_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-31'),
    ]);
  }

  /**
   * @covers ::__construct
   */
  public function testInvalidStartDate() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The "start_date" property must be an instance of Drupal\Core\Datetime\DrupalDateTime.');
    $charge = new Charge([
      'title' => 'My subscription',
      'unit_price' => new Price('99.99', 'USD'),
      'start_date' => 'INVALID',
      'end_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-31'),
    ]);
  }

  /**
   * @covers ::__construct
   * @covers ::getTitle
   * @covers ::getQuantity
   * @covers ::getUnitPrice
   * @covers ::getStartDate
   * @covers ::getEndDate
   */
  public function testCharge() {
    $purchased_entity = $this->prophesize(PurchasableEntityInterface::class)->reveal();
    $charge = new Charge([
      'purchased_entity' => $purchased_entity,
      'title' => 'My subscription',
      'quantity' => '2',
      'unit_price' => new Price('99.99', 'USD'),
      'start_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-01'),
      'end_date' => DrupalDateTime::createFromFormat('Y-m-d', '2017-01-31'),
    ]);

    $this->assertEquals($purchased_entity, $charge->getPurchasedEntity());
    $this->assertEquals('My subscription', $charge->getTitle());
    $this->assertEquals('2', $charge->getQuantity());
    $this->assertEquals(new Price('99.99', 'USD'), $charge->getUnitPrice());
    $this->assertEquals('2017-01-01', $charge->getStartDate()->format('Y-m-d'));
    $this->assertEquals('2017-01-31', $charge->getEndDate()->format('Y-m-d'));
  }

}
