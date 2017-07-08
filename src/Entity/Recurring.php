<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the recurring entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_recurring",
 *   label = @Translation("Recurring"),
 *   label_singular = @Translation("Recurring"),
 *   label_plural = @Translation("Recurrings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count recurring",
 *     plural = "@count recurrings",
 *   ),
 *   bundle_label = @Translation("Recurring type"),
 *   handlers = {
 *     "access" = "Drupal\commerce_recurring\RecurringAccessControlHandler",
 *     "event" = "Drupal\commerce_recurring\Event\RecurringEvent",
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "list_builder" = "Drupal\commerce_recurring\RecurringListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_recurring\Form\RecurringForm",
 *       "add" = "Drupal\commerce_recurring\Form\RecurringForm",
 *       "edit" = "Drupal\commerce_recurring\Form\RecurringForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_recurring",
 *   admin_permission = "administer recurrings",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "recurring_id",
 *     "label" = "recurring_number",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/recurrings/{commerce_recurring}",
 *     "edit-form" = "/admin/commerce/recurrings/{commerce_recurring}/edit",
 *     "user-edit-form" = "/user/{user}/recurrings/{commerce_recurring}/edit",
 *     "delete-form" = "/admin/commerce/recurrings/{commerce_recurring}/delete",
 *     "delete-multiple-form" = "/admin/commerce/recurrings/delete",
 *     "reassign-form" = "/admin/commerce/recurrings/{commerce_recurring}/reassign",
 *     "collection" = "/admin/commerce/recurrings"
 *   },
 *   bundle_entity_type = "commerce_recurring_type",
 *   field_ui_base_route = "entity.commerce_recurring_type.edit_form"
 * )
 */
class Recurring extends ContentEntityBase implements RecurringInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['recurring_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recurring number'))
      ->setDescription(t('The recurring number displayed to the customer.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the recurring belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The recurring owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_recurring\Entity\Recurring::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Contact email'))
      ->setDescription(t('The email address associated with the recurring.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_profile'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing profile'))
      ->setDescription(t('Billing profile'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'profile')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['billing']])
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjustments'] = BaseFieldDefinition::create('commerce_adjustment')
      ->setLabel(t('Adjustments'))
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'commerce_adjustment_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['total_price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Total price'))
      ->setDescription(t('The total price of the recurring.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address of the recurring.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('A boolean indicating whether the recurring is enabled.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'settings' => [
          'format' => 'yes-no',
        ],
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Start date'))
      ->setDescription(t('The time when the recurring was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the recurring was last edited.'));

    $fields['start_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start date'))
      ->setDescription(t('The time when the recurring began date.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'short',
        ],
        'weight' => -13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End date'))
      ->setDescription(t('The time when the recurring ends being active.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -12,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'short',
        ],
        'weight' => -12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Due date'))
      ->setDescription(t('The time when the recurring has is next due date.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -11,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp_ago',
        'weight' => -11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['interval'] = BaseFieldDefinition::create('interval')
      ->setLabel(t('Subscription frequency'))
      ->setDescription(t('The time when the recurring must be executed.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'interval_default',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'interval_default',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the order items of a deleted recurring.
    $order_items = [];
    $recurring_orders = [];
    foreach ($entities as $entity) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      foreach ($entity->getOrderItems() as $order_item) {
        $order_items[$order_item->id()] = $order_item;
      }
      /** @var \Drupal\commerce_order\Entity\OrderInterface $recurring_order */
      foreach ($entity->getRecurringOrders() as $recurring_order) {
        $recurring_orders[$recurring_order->id()] = $recurring_order;
      }
    }

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_order_item');
    $order_item_storage->delete($order_items);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $recurring_order_storage */
    $recurring_order_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_order');
    $recurring_order_storage->delete($recurring_orders);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->isNew()) {
      if (!$this->getIpAddress()) {
        $this->setIpAddress(\Drupal::request()->getClientIp());
      }

      if (!$this->getEmail() && $owner = $this->getOwner()) {
        $this->setEmail($owner->getEmail());
      }
    }

    $this->recalculateTotalPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If no recurring number has been set explicitly, set it to the recurring
    // ID.
    if (!$this->getRecurringNumber()) {
      $this->setRecurringNumber($this->id());
      $this->save();
    }

    // Ensure there's a back-reference on each order item.
    foreach ($this->getOrderItems() as $order_item) {
      if ($order_item->order_id->isEmpty()) {
        $order_item->order_id = $this->id();
        $order_item->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addAdjustment(Adjustment $adjustment) {
    $this->get('adjustments')->appendItem($adjustment);
    $this->recalculateTotalPrice();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAdjustment(Adjustment $adjustment) {
    /** @var \Drupal\commerce_order\EntityAdjustableInterface $adjustments */
    $adjustments = $this->get('adjustments');
    $adjustments->removeAdjustment($adjustment);
    $this->recalculateTotalPrice();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdjustments(array $adjustments) {
    $this->set('adjustments', $adjustments);
    $this->recalculateTotalPrice();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustments() {
    /** @var \Drupal\commerce_order\EntityAdjustableInterface $adjustments */
    $adjustments = $this->get('adjustments');

    return $adjustments->getAdjustments();
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfile() {
    return $this->get('billing_profile')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingProfile(ProfileInterface $profile) {
    $this->set('billing_profile', $profile->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingProfileId() {
    return $this->get('billing_profile')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingProfileId($billing_profile_id) {
    $this->set('billing_profile', $billing_profile_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', [$data]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDueDateTime() {
    return $this->get('due_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDueDateTime($timestamp) {
    $this->set('due_date', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->set('status', $enabled);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDateTime() {
    return $this->get('end_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDateTime($timestamp) {
    $this->set('end_date', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalTime() {
    return $this->get('interval')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setIntervalTime($interval) {
    $this->set('interval', $interval);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIpAddress() {
    return $this->get('ip_address')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIpAddress($ip_address) {
    $this->set('ip_address', $ip_address);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderItem(OrderItemInterface $order_item) {
    if (!$this->hasOrderItem($order_item)) {
      $this->get('order_items')->appendItem($order_item);
      $this->recalculateTotalPrice();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOrderItem(OrderItemInterface $order_item) {
    return $this->getOrderItemIndex($order_item) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOrderItem(OrderItemInterface $order_item) {
    $index = $this->getOrderItemIndex($order_item);
    if ($index !== FALSE) {
      $this->get('order_items')->offsetUnset($index);
      $this->recalculateTotalPrice();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItems() {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $order_items */
    $order_items = $this->get('order_items');

    return $order_items->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function hasOrderItems() {
    return !$this->get('order_items')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItems(array $order_items) {
    $this->set('order_items', $order_items);
    $this->recalculateTotalPrice();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRecurringOrder(OrderInterface $order) {
    if (!$this->hasRecurringOrder($order)) {
      $this->get('recurring_orders')->appendItem($order);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRecurringOrder(OrderInterface $order) {
    return $this->getRecurringOrderIndex($order) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRecurringOrder(OrderInterface $order) {
    $index = $this->getRecurringOrderIndex($order);
    if ($index !== FALSE) {
      $this->get('recurring_orders')->offsetUnset($index);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringOrders() {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $orders */
    $orders = $this->get('recurring_orders');

    return $orders->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function hasRecurringOrders() {
    return !$this->get('recurring_orders')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringOrders(array $orders) {
    $this->set('recurring_orders', $orders);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecurringNumber() {
    return $this->get('recurring_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecurringNumber($recurring_number) {
    $this->set('recurring_number', $recurring_number);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDateTime() {
    return $this->get('start_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDateTime($timestamp) {
    $this->set('start_date', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return $this->get('stores');
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->getStores() as $field_item) {
      $store_ids[] = $field_item->target_id;
    }

    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $store_ids) {
    $this->set('stores', $store_ids);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice() {
    if (!$this->get('total_price')->isEmpty()) {
      /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $first_item */
      $first_item = $this->get('total_price')->first();
      return $first_item->toPrice();
    }

    return NULL;
  }

  /**
   * Initializes the recurring currency code.
   *
   * Takes the currency of the first order item if found.
   *
   * @return string|null
   *   The currency code, or NULL if the recurring is in an incomplete state
   *   (no order items, no store).
   */
  protected function initializeCurrencyCode() {
    if ($this->hasOrderItems()) {
      $order_items = $this->getOrderItems();
      $first_order_item = reset($order_items);
      /** @var \Drupal\commerce_price\Price $unit_price */
      $unit_price = $first_order_item->getUnitPrice();
      if ($unit_price) {
        return $unit_price->getCurrencyCode();
      }
    }

    return NULL;
  }

  /**
   * Gets the index of the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return int|bool
   *   The index of the given order item, or FALSE if not found.
   */
  protected function getOrderItemIndex(OrderItemInterface $order_item) {
    $values = $this->get('order_items')->getValue();
    $order_item_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($order_item->id(), $order_item_ids);
  }

  /**
   * Gets the index of the given recurring order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The recurring order entity.
   *
   * @return int|bool
   *   The index of the given recurring order, or FALSE if not found.
   */
  protected function getRecurringOrderIndex(OrderInterface $order) {
    $values = $this->get('recurring_orders')->getValue();
    $order_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($order->id(), $order_ids);
  }

  /**
   * Recalculates the order item total price.
   */
  protected function recalculateTotalPrice() {
    /** @var \Drupal\commerce_price\Price $total_price */
    $total_price = $this->getTotalPrice();
    if ($total_price) {
      $currency_code = $total_price->getCurrencyCode();
    }
    else {
      $currency_code = $this->initializeCurrencyCode();
      if (!$currency_code) {
        // The recurring object is not complete enough to have a total price
        // yet.
        return;
      }
    }

    $total_price = new Price('0', $currency_code);
    foreach ($this->getOrderItems() as $order_item) {
      $total_price = $total_price->add($order_item->getTotalPrice());
      foreach ($order_item->getAdjustments() as $adjustment) {
        $adjustment_total = $adjustment->getAmount()
          ->multiply($order_item->getQuantity());
        $total_price = $total_price->add($adjustment_total);
      }
    }
    foreach ($this->getAdjustments() as $adjustment) {
      $total_price = $total_price->add($adjustment->getAmount());
    }
    $this->set('total_price', $total_price);
  }

}
