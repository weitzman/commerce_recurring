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

  $events['commerce_recurring_rules_event_failed_payment'] = array(
    'label' => t('A payment fails for a recurring order'),
    'variables' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Processed order', array(), array('context' => 'a drupal commerce order')),
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

  $events['commerce_recurring_rules_event_initialise_order'] = array(
    'label' => t('An order is initialised as a recurring set master'),
    'variables' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Created order', array(), array('context' => 'a drupal commerce order')),
      ),
    ),
    'group' => t('Commerce Recurring'),
    'access' => 'commerce_order_rules_access'
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
    'parameter' => array(),
    'provides' => array(
      'orders' => array(
        'type' => 'list<commerce_order>',
        'label' => t('Recurring Orders'),
        'description' => t('Recurring orders'),
      ),
    ),
    'group' => t('Commerce Recurring'),
  );

  $actions['commerce_recurring_load_payments_due'] = array(
    'label' => t('Load recurring orders with due payments'),
    'parameter' => array(),
    'provides' => array(
      'orders' => array(
        'type' => 'list<commerce_order>',
        'label' => t('Recurring Orders'),
        'description' => t('Due recurring orders'),
      ),
    ),
    'group' => t('Commerce Recurring'),
  );

  $actions['commerce_recurring_process_payment'] = array(
    'label' => t('Process payments against recurring orders'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Due order'),
      ),
    ),
    'group' => t('Commerce Recurring'),
  );

  $actions['commerce_recurring_initialise_recurring'] = array(
    'label' => t('Mark an order as a recurring master'),
    'parameter' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Order'),
        'description' => t('The master order'),
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
  commerce_order_save($recurring_order);

  // Wrap the order for easy access to field data.
  $recurring_order_wrapper = entity_metadata_wrapper('commerce_order', $recurring_order);

  // Wrap the master order for easy access to field data.
  $master_order_wrapper = entity_metadata_wrapper('commerce_order', $master_order);
  foreach ($master_order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
    // Load and validate the specified product ID.
    $product = $line_item_wrapper->commerce_product->value();
    if ($product->type != 'recurring') {
      // We don't need this item
      continue;
    }
    $interval = field_get_items('commerce_product', $product, 'commerce_recurring_interval');
    // Get first value
    $interval = $interval[0];
    $interval_label = interval_format_interval($interval);

    // Create the new product line item.
    $new_line_item = commerce_product_line_item_new(
      $product, $line_item_wrapper->quantity->value(), $recurring_order->order_id, array(), $line_item_wrapper->type->value());

    $new_line_item->line_item_label = t('Recurring !old_label (charged every !interval)',
                                        array ('!old_label' => $new_line_item->line_item_label,
                                               '!interval' => $interval_label));

    // Save the line item now so we get its ID.
    commerce_line_item_save($new_line_item);

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
  // Mark as unprocessed
  $recurring_order->commerce_recurring_payment[LANGUAGE_NONE][0]['value'] = 0;

  // Save the updated order.
  commerce_order_save($recurring_order);

  // Invoke rules event to notify others that the new order has been created
  rules_invoke_all('commerce_recurring_rules_event_new_order', $recurring_order);

  // Get the due date
  $due_date = $master_order_wrapper->commerce_recurring_next_due->value();

  // Create an object
  $due_date_obj = new DateObject($due_date, date_default_timezone(), 'U');

  // Apply the inteval
  if ($interval && interval_apply_interval($due_date_obj, $interval)) {
    // Update the parent next due
    $master_order->commerce_recurring_next_due[LANGUAGE_NONE][0]['value'] = $due_date_obj->format('U');
    // Save the parent
    commerce_order_save($master_order);
  }
  watchdog('Commerce Recurring', 'Generated new recurring order @new_id as child of recurring master @master_id.',
           array('@master_id' => $master_order->order_id, '@new_id' => $recurring_order->order_id), WATCHDOG_NOTICE);

  return array ('recurring_order' => $recurring_order);
}

/**
 * Rules action: mark an order as a recurring master
 *
 * @param $order object
 *   The order object
 */
function commerce_recurring_initialise_recurring($order) {
  // Wrap the order for easy access to field data.
  $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

  $interval = FALSE;
  foreach ($order_wrapper->commerce_line_items as $line_item) {
    // Load and validate the specified product ID.
    $product = $line_item->commerce_product->value();

    if ($product->type != 'recurring') {
      // We don't need this item
      continue;
    }
    // We've got an interval
    $interval = field_get_items('commerce_product', $product, 'commerce_recurring_interval');
    break;
  }

  if (!empty($interval)) {
    $due_dt = new DateObject('now');
    interval_apply_interval($due_dt, $interval[0]);
    $order->commerce_recurring_next_due[LANGUAGE_NONE][0]['value'] = $due_dt->format('U');
    // Save the updated order.
    commerce_order_save($order);

    // Invoke rules event to notify others that the new order has been created
    rules_invoke_all('commerce_recurring_rules_event_initialise_order', $order);

    // Log it
    watchdog('Commerce Recurring', 'Initialised order @id as new recurring master.', array('@id' => $order->order_id), WATCHDOG_NOTICE);
  }

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
      $product = $line_item_wrapper->commerce_product->value();
      if ($product->type == 'recurring') {
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

    $next_due = $order_wrapper->commerce_recurring_next_due->value();
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
    return array('orders' => commerce_order_load_multiple(array_keys($results['commerce_order'])));
  }
  return array('orders' => array());
}

/**
 * Rules action callback: Load orders
*/
function commerce_recurring_load_payments_due() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'commerce_order');
  $query->entityCondition('bundle', 'recurring_order');
  $now = new DateObject('now');
  $query->fieldCondition('commerce_recurring_payment_due', 'value', $now->format('U'), '<=');
  $query->fieldCondition('commerce_recurring_payment', 'value', 0, '=');
  $query->range(0, variable_get('commerce_recurring_batch_process', 20));
  $results = $query->execute();
  if (!empty($results['commerce_order'])) {
    return array('orders' => commerce_order_load_multiple(array_keys($results['commerce_order'])));
  }
  return array('orders' => array());
}

/**
 * Rules callback to process a payment against an order
 * @param $order object
 *   The Commerce Order Object
 *
*/
function commerce_recurring_process_payment($order) {
  $wrapper = entity_metadata_wrapper('commerce_order', $order);
  $parent_order = $wrapper->commerce_recurring_parent_order->value();
  if ($parent_order && !empty($parent_order->order_id) &&
      ($last_payment_transaction_id = commerce_recurring_fetch_last_payment_transaction_id($parent_order->order_id))) {
    // We load the payment details and process it here
    $last_payment_transaction = commerce_payment_transaction_load($last_payment_transaction_id);
    // Get the payment instance and method
    $instance_id = $parent_order->data['payment_method'];
    $payment_method = commerce_payment_method_instance_load($instance_id);
    if (!empty($payment_method['cardonfile']['charge callback']) &&
        ($function = $payment_method['cardonfile']['charge callback']) &&
        function_exists($payment_method['cardonfile']['charge callback'])) {
      $result = $function($order, $parent_order);
      if (!$result) {
        watchdog('commerce_recurring', "Error occured whilst processing recurring transaction payment for order @order_id's - the payment method @method commerce card on file charge callback reported an error.",
             array('@order_id' => $order->order_id,
                   '@method' => $payment_method['short_title']), WATCHDOG_ERROR);
        module_invoke_all('commerce_recurring_failed_payment', $order);
        // Invoke the product prepare event with the shopping cart order.
        rules_invoke_all('commerce_recurring_rules_event_failed_payment', $order);
      }
      else {
        watchdog('commerce_recurring', "Processed recurring transaction payment for order @order_id's - using @method payment method - return code: @return.",
             array('@order_id' => $order->order_id,
                   '@return' => $result,
                   '@method' => $payment_method['short_title']), WATCHDOG_INFO);
        // Now we mark the order as payment processed so it does not load again.
        $wrapper->commerce_recurring_payment = 1;
        $wrapper->save();
      }
    }
    else {
      watchdog('commerce_recurring', "Could not fetch process recurring transaction for order @order_id's - the payment method @method does not provide a valid commerce card on file charge callback.",
             array('@order_id' => $order->order_id,
                   '@method' => $payment_method['short_title']), WATCHDOG_ERROR);
    }
  }
  else {
    watchdog('commerce_recurring', "Could not fetch payment transaction for order @order_id's parent order.",
             array('@order_id' => $order->order_id), WATCHDOG_ERROR);
    return;
  }
}

/**
 * Util to fetch the last payment transaction made against an order
 * @param $order_id int
 *   The order id
 *
 * @return object
 *   commerce_payment_transaction
*/
function commerce_recurring_fetch_last_payment_transaction_id($order_id) {
  $query = db_select("commerce_payment_transaction", 'cpt');
  $query->fields('cpt', array('transaction_id'));
  $query->range(0, 1);
  $query->condition('cpt.order_id', $order_id);
  $query->orderBy('cpt.created', 'DESC');
  $result = $query->execute();
  foreach ($result as $row) {
    return $row->transaction_id;
  }
  return FALSE;
}
