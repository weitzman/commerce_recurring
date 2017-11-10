<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

/**
 * Provides the license subscription type.
 *
 * Differs from the product_variation subscription type by also tracking the
 * related license, and ensuring its state reflects the subscription state.
 *
 * @CommerceSubscriptionType(
 *   id = "license",
 *   label = @translation("License"),
 *   purchasable_entity_type = "commerce_product_variation",
 * )
 */
class License extends ProductVariation {}
