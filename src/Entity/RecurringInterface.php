<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the interface for recurrings.
 */
interface RecurringInterface extends EntityAdjustableInterface, EntityStoreInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the billing profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The billing profile entity.
   */
  public function getBillingProfile();

  /**
   * Sets the billing profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The billing profile entity.
   *
   * @return $this
   */
  public function setBillingProfile(ProfileInterface $profile);

  /**
   * Gets the billing profile ID.
   *
   * @return int
   *   The billing profile ID.
   */
  public function getBillingProfileId();

  /**
   * Sets the billing profile ID.
   *
   * @param int $billing_profile_id
   *   The billing profile ID.
   *
   * @return $this
   */
  public function setBillingProfileId($billing_profile_id);

  /**
   * Gets the recurring creation timestamp.
   *
   * @return int
   *   Creation timestamp of the recurring.
   */
  public function getCreatedTime();

  /**
   * Sets the recurring creation timestamp.
   *
   * @param int $timestamp
   *   The recurring creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the additional data stored in this recurring.
   *
   * @return array
   *   An array of additional data.
   */
  public function getData();

  /**
   * Sets random information related to this recurring.
   *
   * @param array $data
   *   An array of additional data.
   *
   * @return $this
   */
  public function setData($data);

  /**
   * Gets the recurring has is next due date timestamp.
   *
   * @return int
   *   Next due date timestamp of the recurring.
   */
  public function getDueDateTime();

  /**
   * Sets the recurring has is next due date timestamp.
   *
   * @param int $timestamp
   *   The recurring next due date timestamp.
   *
   * @return $this
   */
  public function setDueDateTime($timestamp);

  /**
   * Gets the email address associated with the recurring.
   *
   * @return string
   *   The recurring mail.
   */
  public function getEmail();

  /**
   * Sets the recurring mail.
   *
   * @param string $mail
   *   The email address associated with the recurring.
   *
   * @return $this
   */
  public function setEmail($mail);

  /**
   * Returns the recurring enabled status indicator.
   *
   * @return bool
   *   TRUE if the recurring is enabled.
   */
  public function isEnabled();

  /**
   * Sets the enabled status of a node.
   *
   * @param bool $enabled
   *   TRUE to set this recurring to enabled, FALSE to set it to disabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Gets the recurring ends being active timestamp.
   *
   * @return int
   *   Ends being active timestamp of the recurring.
   */
  public function getEndDateTime();

  /**
   * Sets the recurring ends being active timestamp.
   *
   * @param int $timestamp
   *   The recurring ends being active timestamp.
   *
   * @return $this
   */
  public function setEndDateTime($timestamp);

  /**
   * Gets the recurring must be executed period.
   *
   * @return array
   *   The time when the recurring must be executed.
   *   - interval: (int) The number of multiples of the period.
   *   - period: (varchar) The period machine name.
   */
  public function getIntervalTime();

  /**
   * Sets the recurring must be repeated period.
   *
   * @param array $interval
   *   The time when the recurring must be executed.
   *   - interval: (int) The number of multiples of the period.
   *   - period: (varchar) The period machine name.
   *
   * @return $this
   */
  public function setIntervalTime($interval);

  /**
   * Gets the recurring IP address.
   *
   * @return string
   *   The IP address.
   */
  public function getIpAddress();

  /**
   * Sets the recurring IP address.
   *
   * @param string $ip_address
   *   The IP address.
   *
   * @return $this
   */
  public function setIpAddress($ip_address);

  /**
   * Adds a line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return $this
   */
  public function addLineItem(LineItemInterface $line_item);

  /**
   * Checks whether the recurring has a given line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return bool
   *   TRUE if the line item was found, FALSE otherwise.
   */
  public function hasLineItem(LineItemInterface $line_item);

  /**
   * Removes a line item.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   *
   * @return $this
   */
  public function removeLineItem(LineItemInterface $line_item);

  /**
   * Gets the line items.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   *   The line items.
   */
  public function getLineItems();

  /**
   * Gets whether the recurring has line items.
   *
   * @return bool
   *   TRUE if the recurring has line items, FALSE otherwise.
   */
  public function hasLineItems();

  /**
   * Sets the line items.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface[] $line_items
   *   The line items.
   *
   * @return $this
   */
  public function setLineItems(array $line_items);

  /**
   * Adds a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return $this
   */
  public function addRecurringOrder(OrderInterface $order);

  /**
   * Checks whether the recurring has a given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return bool
   *   TRUE if the recurring order was found, FALSE otherwise.
   */
  public function hasRecurringOrder(OrderInterface $order);

  /**
   * Removes a recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order.
   *
   * @return $this
   */
  public function removeRecurringOrder(OrderInterface $order);

  /**
   * Gets the recurring orders.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface[]
   *   The recurring orders.
   */
  public function getRecurringOrders();

  /**
   * Gets whether the recurring has recurring orders.
   *
   * @return bool
   *   TRUE if the recurring has recurring orders, FALSE otherwise.
   */
  public function hasRecurringOrders();

  /**
   * Sets the recurring orders.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $orders
   *   The recurring orders.
   *
   * @return $this
   */
  public function setRecurringOrders(array $orders);

  /**
   * Gets the recurring number.
   *
   * @return string
   *   The recurring number.
   */
  public function getRecurringNumber();

  /**
   * Sets the recurring number.
   *
   * @param string $recurring_number
   *   The recurring number.
   *
   * @return $this
   */
  public function setRecurringNumber($recurring_number);

  /**
   * Gets the recurring began date timestamp.
   *
   * @return int
   *   Began date timestamp of the recurring.
   */
  public function getStartDateTime();

  /**
   * Sets the recurring began date timestamp.
   *
   * @param int $timestamp
   *   The recurring began date timestamp.
   *
   * @return $this
   */
  public function setStartDateTime($timestamp);

  /**
   * Gets the recurring total price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The recurring total price, or NULL.
   */
  public function getTotalPrice();

}
