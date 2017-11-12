<?php

namespace Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType;

/**
 * Provides the standalone subscription type (not backed by a purchased entity).
 *
 * @CommerceSubscriptionType(
 *   id = "standalone",
 *   label = @translation("standalone"),
 * )
 */
class Standalone extends SubscriptionTypeBase {}
