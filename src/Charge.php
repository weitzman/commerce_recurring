<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Represents a charge.
 *
 * Charges are returned from the subscription type, and then mapped to new
 * or existing recurring order items. This allows order items to be reused
 * when possible.
 */
final class Charge {

  /**
   * The purchased entity, when available.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface|null
   */
  protected $purchasedEntity;

  /**
   * The title.
   *
   * @var string
   */
  protected $title;

  /**
   * The quantity.
   *
   * @var string
   */
  protected $quantity;

  /**
   * The unit price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $unitPrice;

  /**
   * The start date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $startDate;

  /**
   * The end date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $endDate;

  /**
   * Constructs a new Charge object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['title', 'unit_price', 'start_date', 'end_date'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (isset($definition['purchased_entity']) && !($definition['purchased_entity'] instanceof PurchasableEntityInterface)) {
      throw new \InvalidArgumentException(sprintf('The "purchased_entity" property must be an instance of %s.', PurchasableEntityInterface::class));
    }
    if (!$definition['unit_price'] instanceof Price) {
      throw new \InvalidArgumentException(sprintf('The "unit_price" property must be an instance of %s.', Price::class));
    }
    foreach (['start_date', 'end_date'] as $property) {
      if (!($definition[$property] instanceof DrupalDateTime)) {
        throw new \InvalidArgumentException(sprintf('The "%s" property must be an instance of %s.', $property, DrupalDateTime::class));
      }
    }

    $this->purchasedEntity = isset($definition['purchased_entity']) ? $definition['purchased_entity'] : NULL;
    $this->title = $definition['title'];
    $this->quantity = isset($definition['quantity']) ? $definition['quantity'] : '1';
    $this->unitPrice = $definition['unit_price'];
    $this->startDate = $definition['start_date'];
    $this->endDate = $definition['end_date'];
  }

  /**
   * Gets the purchased entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchased entity, or NULL if the charge is not backed by one.
   */
  public function getPurchasedEntity() {
    return $this->purchasedEntity;
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Gets the quantity.
   *
   * @return string
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the unit price.
   *
   * @return \Drupal\commerce_price\Price
   *   The unit price.
   */
  public function getUnitPrice() {
    return $this->unitPrice;
  }

  /**
   * Gets the start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date.
   */
  public function getStartDate() {
    return $this->startDate;
  }

  /**
   * Gets the end date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The end date.
   */
  public function getEndDate() {
    return $this->endDate;
  }

}
