<?php
/**
 * @file
 * Rules integration for recurring entities.
 *
 * @addtogroup rules
 * @{
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_recurring_rules_event_info() {
  $events = array();

  $events['commerce_recurring_paid_full'] = array(
    'label' => t('Recurring order is paid in full'),
    'group' => t('Commerce recurring'),
    'variables' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Commerce order'),
      ),
      'commerce_recurring' => array(
        'type' => 'commerce_recurring',
        'label' => t('Commerce recurring entity'),
      ),
    ),
  );

  $events['commerce_recurring_stop_recurring'] = array(
    'label' => t('Stop a recurring entity'),
    'group' => t('Commerce recurring'),
    'variables' => array(
      'commerce_recurring' => array(
        'type' => 'commerce_recurring',
        'label' => t('Commerce recurring entity'),
      ),
    ),
  );

  return $events;
}


/**
 * Implements hook_rules_action_info().
 */
function commerce_recurring_rules_action_info() {
  $actions = array();

  $actions['commerce_recurring_set_price'] = array(
    'label' => t('Replace listing price by the initial price for recurring'),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
      'listing_price' => array(
        'type' => 'commerce_price',
        'label' => t('Price used for listings'),
      ),
      'initial_price' => array(
        'type' => 'commerce_price',
        'label' => t('Price used for initial recurring'),
      ),
      'recurring_price' => array(
        'type' => 'commerce_price',
        'label' => t('Price used for consequent recurrings'),
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_set_price',
    ),
  );

  $actions['commerce_recurring_get_recurring_line_items'] = array(
    'label' => t('Get all the line items containing recurring products from an order'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
      ),
    ),
    'provides' => array(
      'commerce_line_items' => array(
        'label' => t('Line items with recurring products'),
        'type' => 'list<commerce_line_item>',
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_get_recurring_line_items',
    ),
  );

  $actions['commerce_recurring_get_due_items'] = array(
    'label' => t('Get the recurring entities about to due'),
    'parameter' => array(
      'number_items' => array(
        'type' => 'decimal',
        'label' => t('Number of items'),
        'description' => t('Restrict the number of items to retrieve'),
        'default value' => 0,
      ),
      'timestamp' => array(
        'type' => 'date',
        'label' => t('Due date'),
      ),
    ),
    'provides' => array(
      'commerce_recurring_entities' => array(
        'label' => t('Commerce recurring entities'),
        'type' => 'list<commerce_recurring>',
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_get_due_items',
    ),
  );

  $actions['commerce_recurring_generate_order_from_recurring'] = array(
    'label' => t('Generate the order associated to the recurring entity'),
    'description' => t('Use the order relationship from the recurring entity to generate the associated order'),
    'parameter' => array(
      'commerce_recurring' => array(
        'type' => 'commerce_recurring',
        'label' => t('Commerce recurring'),
      ),
      'timestamp' => array(
        'type' => 'date',
        'label' => t('Due date'),
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_generate_order_from_recurring',
    ),
  );

  // @TODO Break the commerce product parameter in components for allowing to
  // set the initial dates, prices more granular.
  $actions['commerce_recurring_generate_recurring_product'] = array(
    'label' => t('Create / Update a recurring entity from product data'),
    'description' => t('Use the product fields information for creating / updating the recurring entity.'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
      ),
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Commerce line item'),
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_generate_recurring_product',
    ),
  );

  return $actions;
}

/**
 * Implements hook_rules_condition_info().
 */
function commerce_recurring_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_recurring_order_contains_recurring_product'] = array(
    'label' => t('Order contains a recurring product'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
      ),
    ),
    'group' => t('Commerce recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_rules_order_contains_recurring_product',
    ),
  );

  return $conditions;
}

/**
 * Action callback to override the listing price by the one in initial price of
 * the recurring framework.
 *
 * @param $line_item
 *   Commerce line item affected by the price replacement.
 */
function commerce_recurring_rules_set_price($line_item, $listing_price, $initial_price, $recurring_price) {
  // If the line item contains a product, we replace the price by the initial
  // price for recurring.
  if (commerce_line_items_quantity(array($line_item), commerce_product_line_item_types())) {
    $price = array();
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $product_wrapper = $line_item_wrapper->commerce_product;
    $product = $product_wrapper->value();
    // If the product is recurring we act.
    if (commerce_recurring_product_is_recurring($product)) {
      if (!empty($line_item->order_id)) {
        $order = commerce_order_load($line_item->order_id);
      }
      if (!empty($line_item->data['recurring_entity'])) {
        $recurring_entity = entity_load_single('commerce_recurring', $line_item->data['recurring_entity']);
        $recurring_wrapper = entity_metadata_wrapper('commerce_recurring', $recurring_entity);
        // Case: Recurring price.
        $recurring_orders = $recurring_wrapper->commerce_recurring_order->value();
        // Empty the price components to recalculate them.
        $line_item->commerce_unit_price[LANGUAGE_NONE][0]['data']['components'] = array();
        if (count($recurring_orders) >= 1) {
          $price = array(
            'amount' => $recurring_price['amount'],
            'currency_code' => $recurring_price['currency_code'],
            'data' => $recurring_price['data'],
          );
        }
        else {
          // This would be the first order so we use initial price.
          $price = array(
            'amount' => $initial_price['amount'],
            'currency_code' => $initial_price['currency_code'],
            'data' => $initial_price['data'],
          );
        }
      }
      else {
        // Case: Listing price / Cart price.
        if (empty($line_item->line_item_id) || commerce_cart_order_is_cart($order)) {
          // Empty the price components to recalculate them.
          $line_item->commerce_unit_price[LANGUAGE_NONE][0]['data']['components'] = array();
          $price = array(
            'amount' => $listing_price['amount'],
            'currency_code' => $listing_price['currency_code'],
            'data' => $listing_price['data'],
          );
        }
        //@TODO: // Case: Initial price.
        //@TODO: Fix cart listing when displaying the cart with the product.
      }

      if (!empty($price)) {
        // Alter the base price to the current one.
        $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add(
                $line_item_wrapper->commerce_unit_price->value(),
                'base_price',
                $price,
                TRUE
        );
        $line_item_wrapper->commerce_unit_price->amount = $price['amount'];
      }
    }
  }
}

/**
 * Return the recurring products present inside an order.
 */
function commerce_recurring_rules_get_recurring_line_items($order) {
  $line_items = commerce_recurring_order_load_recurring_line_items($order);
  return array('commerce_line_items' => $line_items[$order->order_id]);
}

/**
 * Return recurring entities with due dates.
 */
function commerce_recurring_rules_get_due_items($number_items = 0, $due_date = NULL) {
  if (empty($due_date)) {
    $due_date = new DateObject();
    $due_date = $due_date->getTimestamp();
  }

  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'commerce_recurring', '=');
  $query->propertyCondition('status', TRUE, '=');
  $query->propertyCondition('due_date', $due_date, '<');
  if ($number_items > 0) {
    $query->range(0, $number_items);
  }
  $result = $query->execute();

  $recurring_entities = array();
  if (!empty($result['commerce_recurring'])) {
    foreach($result['commerce_recurring'] as $recurring_entity) {
      $recurring_entities[] = entity_load_single('commerce_recurring', $recurring_entity->commerce_recurring_id);
    }
  }
  return array('commerce_recurring_entities' => $recurring_entities);
}

/**
 * Generate the recurring entity using the product information.
 */
function commerce_recurring_rules_generate_recurring_product($order, $line_item) {
  $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
  $product_wrapper = $line_item_wrapper->commerce_product;
  $product = $product_wrapper->value();

  // We need to check if there's already an order with that recurring entity.
  if (empty($order->data['recurring_entity'])) {
    // If we're creating the recurring entity, we need to set the following:
    // commerce_price at the moment of creation (product price).
    // Start date: now().
    // Due date: Start date + initial period from product.
    // End date: optional, start date + expiration period.
    $date = new DateObject();
    $start_date = $date->getTimestamp();

    $due_date = clone $date;
    if (!empty($product->commerce_recurring_ini_period)) {
      $initial_interval = $product_wrapper->commerce_recurring_ini_period->value();
      if (empty($initial_interval)) {
        if (!empty($product->commerce_recurring_rec_period)) {
          $initial_interval = $product_wrapper->commerce_recurring_rec_period->value();
        }
      }
    }
    if (!empty($initial_interval)) {
      interval_apply_interval($due_date, $initial_interval, TRUE);
    }
    $due_date = $due_date->getTimestamp();

    if (!empty($product->commerce_recurring_end_period)) {
      $end_interval = $product_wrapper->commerce_recurring_end_period->value();
      interval_apply_interval($date, $end_interval, TRUE);
      $end_date = $date->getTimestamp();
    }

    $values = array(
      'product_id' => $product->product_id,
      'order_ids' => array($order->order_id),
      'uid' => $order->uid,
      'start_date' => $start_date,
      'due_date' => $due_date,
      'end_date' => isset($end_date) ? $end_date : NULL,
    );
    $recurring_entity = commerce_recurring_new($values);
    // Add the price at the moment of purchase.
    $recurring_entity->commerce_recurring_fixed_price[LANGUAGE_NONE][0] = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);;
    entity_save('commerce_recurring', $recurring_entity);
  }
  else {
    // If we're updating the recurring entity, we need to update this values:
    // Add the new order at the end of the list of orders.
    // Due date: previous due date + recurring period.
    // End date update.
    if ($recurring_entity = entity_load_single('commerce_recurring', $order->data['recurring_entity'])) {
      $recurring_entity->commerce_recurring_order[LANGUAGE_NONE][]['target_id'] = $order->order_id;
      if (!empty($product->commerce_recurring_rec_period)) {
        $recurring_interval = $product_wrapper->commerce_recurring_rec_period->value();
        if (!empty($recurring_interval)) {
          $date = new DateObject($recurring_entity->due_date);
          interval_apply_interval($date, $recurring_interval, TRUE);
          $recurring_entity->due_date = $date->getTimestamp();
          entity_save('commerce_recurring', $recurring_entity);
        }
      }
      else {
        // If there's no more due dates, deactivate the recurring entity.
        commerce_recurring_stop_recurring($recurring_entity);
        return;
      }
    }
    else {
      return;
    }
  }

  rules_invoke_event('commerce_recurring_paid_full', $order, $recurring_entity);
}

/**
 * Generate the orders from the recurring entity.
 */
function commerce_recurring_rules_generate_order_from_recurring($recurring_entity, $due_date) {
  // First we get the due date, default to now if empty.
  if (empty($due_date)) {
    $due_date = new DateObject();
    $due_date = $due_date->getTimestamp();
  }
  // Get the product out of the recurring entity.
  $recurring_wrapper = entity_metadata_wrapper('commerce_recurring', $recurring_entity);
  $product_wrapper = $recurring_wrapper->commerce_recurring_ref_product;

  // @TODO Add a check for the latest order of the recurring entity.
  // If that wasn't paid or the order has not been completed, stop the
  // recurring.

  // If the due date is over the end date, stop the recurring.
  if (!empty($recurring_entity->end_date) && ($due_date > $recurring_entity->end_date)) {
    commerce_recurring_stop_recurring($recurring_entity);
    return;
  }

  // Generate a new order with that product.
  $order = commerce_order_new($recurring_entity->uid, 'cart');
  $order->log = t('Created from recurring entity.');
  // Add the recurring entity id to the data array from the order to keep track.
  $order->data['recurring_entity'] = $recurring_wrapper->commerce_recurring_id->value();
  commerce_order_save($order);

  $line_item = commerce_product_line_item_new($product_wrapper->value());
  $line_item->order_id = $order->order_id;
  // We need to add a flag to the line item to be able to know that we're in a
  // recurring context in rules.
  $line_item->data['recurring_entity'] = $recurring_wrapper->commerce_recurring_id->value();
  rules_invoke_event('commerce_product_calculate_sell_price', $line_item);
  commerce_line_item_save($line_item);
  entity_get_controller('commerce_line_item')->resetCache(array($line_item->line_item_id));

  $order->commerce_line_items[LANGUAGE_NONE][0]['line_item_id'] = $line_item->line_item_id;
  commerce_order_save($order);

  $recurring_wrapper->commerce_recurring_order[] = $order->order_id;
  entity_save('commerce_recurring', $recurring_wrapper->value());
}

/**
 * Condition to check wether the order has some recurring products on it.
 *
 * @param $order
 *   Commerce order to check.
 */
function commerce_recurring_rules_order_contains_recurring_product($order) {
  return count(commerce_recurring_order_load_recurring_line_items($order)) > 0 ? TRUE : FALSE;
}

/**
 * @}
 */
