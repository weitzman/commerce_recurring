<?php

/**
 * Rules integration for recurring triggers.
 */

/**
 * Implements hook_rules_condition_info().
 */
function commerce_recurring_rules_condition_info() {
  $conditions = array();

  $conditions['commerce_recurring_rules_order_is_master'] = array(
    'label' => t('Order is recurring set master'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order in question.'),
      ),
    ),
    'group' => t('Commerce Order'),
  );
  
  $conditions['commerce_recurring_rules_order_contains_recurring'] = array(
    'label' => t('Order contains a recurring product'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The order in question.'),
      ),
    ),
    'group' => t('Commerce Order'),
  );
  return $conditions;
}

/**
 * Implements hook_rules_event_info().
 */
function commerce_recurring_rules_event_info() {
  $events = array();
  // So that we can use the entity_rules_events_variables() helper function.
  module_load_include('inc', 'entity', 'entity.rules');


  $events['commerce_recurring_rules_event_new_order'] = array(
    'label' => t('A new recurring order is created'),
    'variables' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Created order', array(), array('context' => 'a drupal commerce order')),
      ),
    ),
    'group' => t('Commerce Recurring'),
    'access' => 'commerce_order_rules_access'
  );
  
  $variables = array_merge(
    entity_rules_events_variables('commerce_order', t('Order', array(), array('context' => 'a drupal commerce order')), TRUE, TRUE),
    entity_rules_events_variables('commerce_payment_transaction', t('Last completed transaction'), TRUE)
  );
  
  $events['commerce_recurring_rules_event_new_payment'] = array(
    'label' => t('A new recurring order payment created'),
    'variables' => $variables,
    'group' => t('Commerce Recurring'),
    'access' => 'commerce_payment_rules_access'
  );

  return $events;
}


/**
 * Implements hook_rules_action_info().
 */
function commerce_recurring_rules_action_info() {
  $actions = array();

  $actions['commerce_recurring_process_recurring'] = array(
    'label' => t('Generate new recurring orders'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Master order'),
      ),
    ),
    'provides' => array(
      'recurring_order' => array(
        'type' => 'commerce_order',
        'label' => t('New Order'),
        'description' => t('The new repeat of the order'),
      ),
    ),
    'group' => t('Commerce Recurring'),
  );
  
  $actions['commerce_recurring_load_recurring'] = array(
    'label' => t('Load recurring orders'),
    'parameters' => array(),
    'provides' => array(
      'orders' => array(
        'type' => 'list<commerce_order>',
        'label' => t('Recurring Orders'),
        'description' => t('Recurring orders'),
      ),
    ),
    'group' => t('Commerce Recurring'),
  );

  return $actions;
}

/**
 * Rules action: create a new order duplicting the given line item on
 * the new order.
 *
 * @param $master_order object
 *   The master order object (first in the sequence)
 * @param $line_items array
 *   array of line items on the order
 */
function commerce_recurring_process_recurring($master_order) {
  // Create a new order
  $recurring_order = commerce_order_new($master_order->uid, 'pending', 'recurring_order');

  $recurring_order->commerce_customer_billing = $master_order->commerce_customer_billing;
  // Save it so it gets an order ID and return the full object.
  $recurring_order = commerce_order_save ($recurring_order);

  // Wrap the order for easy access to field data.
  $recurring_order_wrapper = entity_metadata_wrapper('commerce_order', $recurring_order);

  foreach ($line_items as $line_item) {
    // Load and validate the specified product ID.
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
    $product_id = $line_item_wrapper->commerce_product->value();
    $product = commerce_product_load($product_id);
    $product_wrapper = entity_metadata_wrapper('commerce_product', $product);
    if ($product->type != 'recurring') {
      // We don't need this item
      continue;
    }
    $interval = $product_wrapper->commerce_recurring_interval->value();
    $interval_label = interval_format_interval($interval);
    
    // Create the new product line item.
    $new_line_item = commerce_product_line_item_new ($product, $line_item->quantity, $recurring_order->order_id);
    $new_line_item->line_item_label = t('Recurring !old_label (charged every !interval)',
                                        array ('!old_label' => $new_line_item->line_item_label,
                                               '!interval' => $interval_label));
  
    // Process the unit price through Rules so it reflects the user's actual
    // purchase price.
    rules_invoke_event('commerce_product_calculate_sell_price', $new_line_item);
  
    // Save the line item now so we get its ID.
    $new_line_item = commerce_line_item_save($new_line_item);
  
    // Add it to the order's line item reference value.
    $recurring_order_wrapper->commerce_line_items[] = $new_line_item;
  }
  
  // Set the parent order
  $recurring_order->commerce_recurring_parent_order[LANGUAGE_NONE][0]['order_id'] = $master_order->order_id;
  // Set the payment due date
  $payment_interval = variable_get('commerce_recurring_payment_interval', 3);
  
  $payment_dt = new DateObject('now');
  if ($payment_interval) {
    $payment_dt->modify("+$payment_interval days");
  }
  $recurring_order->commerce_recurring_payment_due[LANGUAGE_NONE][0]['value'] = $payment_dt->format('U');

  // Save the updated order.
  commerce_order_save($recurring_order);
  
  // Invoke rules event to notify others that the new order has been created
  rules_invoke_all('commerce_recurring_rules_event_new_order', $recurring_order);
  
  // Wrap the master order for easy access to field data.
  $master_order_wrapper = entity_metadata_wrapper('commerce_order', $master_order);
  
  // Get the due date
  $due_date = $master_order_wrapper->commerce_recurring_next_due->value();
  
  // Create an object
  $due_date_obj = new DateObject($due_date['value'], date_default_timezone(), 'U');
  
  // Apply the inteval
  if ($interval && interval_apply_interval($due_date_obj, $interval)) {
    // Update the parent next due
    $master_order->commerce_recurring_next_due[LANGUAGE_NONE][0]['value'] = $due_date_obj->format('U');
    // Save the parent
    commerce_order_save($master_order);
  }  

  return array ('recurring_order' => $recurring_order);
}

/**
 * Condition callback: checks to see if a recurring product exists on an order
 */
function commerce_recurring_rules_order_contains_recurring($order) {
  // If we actually received a valid order...
  if (!empty($order)) {
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    // Populate the array of the quantities of the products on the order.
    foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
      if ($line_item_wrapper->type->value() == 'recurring') {
        return TRUE;
      }
    }
  }
  return FALSE;
}

/**
 * Condition callback: checks to see if a recurring product exists on an order
 */
function commerce_recurring_rules_order_is_master($order) {
  // If we actually received a valid order...
  if (!empty($order)) {
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    $next_due = $order->wrapper->commerce_recurring_next_due->value();
    if (!empty($next_due)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Rules action callback: Load orders
*/
function commerce_recurring_load_recurring() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'commerce_order');
  $query->entityCondition('bundle', 'commerce_order');
  $now = new DateObject('now');
  $query->fieldCondition('commerce_recurring_next_due', 'value', $now->format('U'), '<=');
  $query->range(0, variable_get('commerce_recurring_batch_process', 20));
  $results = $query->execute();
  if (!empty($results['commerce_order'])) {
    return array('recurring_orders' => commerce_order_load_multiple(array_keys($results['commerce_order'])));
  }
  return array('recurring_orders' => array());
}