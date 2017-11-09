<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the subscription entity.
 *
 * @ContentEntityType(
 *   id = "commerce_subscription",
 *   label = @Translation("Subscription"),
 *   label_collection = @Translation("Subscriptions"),
 *   label_singular = @Translation("subscription"),
 *   label_plural = @Translation("subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count subscription",
 *     plural = "@count subscription",
 *   ),
 *   bundle_label = @Translation("Subscription type"),
 *   bundle_plugin_type = "commerce_subscription_type",
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_recurring\SubscriptionListBuilder",
 *     "storage" = "\Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "form" = {
 *       "default" = "\Drupal\commerce_recurring\Form\SubscriptionForm",
 *       "edit" = "\Drupal\commerce_recurring\Form\SubscriptionForm",
 *       "delete" = "\Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_subscription",
 *   admin_permission = "administer commerce_subscription",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "subscription_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/subscriptions/{commerce_subscription}",
 *     "add-page" = "/admin/commerce/subscriptions/add",
 *     "add-form" = "/admin/commerce/subscriptions/{type}/add",
 *     "edit-form" = "/admin/commerce/subscriptions/{commerce_subscription}/edit",
 *     "delete-form" = "/admin/commerce/subscriptions/{commerce_subscription}/delete",
 *     "collection" = "/admin/commerce/subscriptions",
 *   },
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $payment_type_manager = \Drupal::service('plugin.manager.commerce_subscription_type');
    return $payment_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->get('store_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingSchedule() {
    return $this->get('billing_schedule')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingSchedule(BillingScheduleInterface $billing_schedule) {
    $this->set('billing_schedule', $billing_schedule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomer() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomer(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCustomerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethod(PaymentMethodInterface $payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethodId($payment_method_id) {
    $this->set('payment_method', $payment_method_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPurchasedEntity() {
    return !$this->get('purchased_entity')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntity() {
    return $this->get('purchased_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasedEntityId() {
    return $this->get('purchased_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setPurchasedEntity(PurchasableEntityInterface $purchased_entity) {
    $this->set('purchased_entity', $purchased_entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
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
  public function getStartTime() {
    return $this->get('starts')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime($timestamp) {
    $this->set('starts', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    return $this->get('ends')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime($timestamp) {
    $this->set('ends', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    return 'recurring';
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    return 'Recurring order item';
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return $this->getAmount();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (['store_id', 'billing_schedule', 'uid', 'amount'] as $field) {
      if ($this->get($field)->isEmpty()) {
        throw new EntityMalformedException(sprintf('Required subscription field "%s" is empty.', $field));
      }
    }

    $state = $this->getState()->value;
    $original_state = isset($this->original) ? $this->original->getState()->value : '';
    if ($state === 'active' && $original_state !== 'active') {
      if (empty($this->getStartTime())) {
        $this->setStartTime(\Drupal::time()->getRequestTime());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store to which the subscription belongs.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing schedule'))
      ->setDescription(t('The billing schedule.'))
      ->setSetting('target_type', 'commerce_billing_schedule')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The subscribed customer.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method.'))
      ->setSetting('target_type', 'commerce_payment_method')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setReadOnly(TRUE);

    $fields['purchased_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Purchased entity'))
      ->setDescription(t('The purchased entity.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The subscription amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The subscription state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('workflow', 'subscription_default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the subscription was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ]);

    $fields['starts'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Starts'))
      ->setDescription(t('The time when the subscription starts.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ends'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ends'))
      ->setDescription(t('The time when the subscription ends.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
