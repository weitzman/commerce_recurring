<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the license subscription type.
 *
 * Differs from the product_variation subscription type by also tracking the
 * related license, and ensuring its state reflects the subscription state.
 *
 * @CommerceSubscriptionType(
 *   id = "license",
 *   label = @Translation("License"),
 *   purchasable_entity_type = "commerce_product_variation",
 * )
 */
class License extends ProductVariation {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    // @todo Move the plugin to commerce_license.
    return $fields;

    $fields['license'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('License'))
      ->setDescription(t('The license associated with the subscription.'))
      ->setSetting('target_type', 'commerce_license')
      ->setSetting('handler', 'default')
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionCreate(SubscriptionInterface $subscription, OrderItemInterface $order_item) {
    // Transfer the license ID from the order item to the subscription.
    if ($order_item->hasField('license')) {
      $subscription->set('license', $order_item->get('license')->target_id);
    }
  }

}
