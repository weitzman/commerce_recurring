<?php

/**
 * Rules integration for recurring triggers.
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_recurring_rules_event_info() {
  $events = array();

  $events['commerce_recurring_processing_completed'] = array(
    'label' => t('Recurring action(s) on line item completed successfully'),
    'variables' => array(
      'line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
     ),
    'group' => t('Commerce Recurring'),
  );

  return $events;
}


/**
 * Implements hook_rules_action_info().
 */
function commerce_recurring_rules_action_info() {
  $actions = array();

  $actions['commerce_recurring_process_recurring'] = array(
    'label' => t('Trigger applicable recurring line items'),
    'parameter' => array(
      'view' => array('type' => 'text', 'label' => t('View name')),
    ),
    'provides' => array(
      'line_items_to_trigger' => array(
        'type' => 'list<commerce_line_item>',
        'label' => t('Line items'),
        'description' => t('All line items that must be triggered'),
      ),
    ),
    'group' => t('Commerce Recurring'),
    'base' => 'commerce_recurring_process_view',
    /*
    'callbacks' => array(
      'execute' => 'commerce_recurring_process_view',
    ),
    */
  );
  $actions['commerce_recurring_create_recurring_order'] = array(
    'label' => t('Create a new order for the recurring item'),
    'parameter' => array(
      'line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
    ),
    'provides' => array(
      'new_order' => array(
        'type' => 'commerce_order', // What we actually provide is the view row, not sure how to deal with that
        'label' => t('New order'),
        'description' => t('Newly created order'),
      ),
    ),
    'group' => t('Commerce Recurring'),
    'base' => 'commerce_recurring_create_recurring_order',
  );
  $actions['commerce_recurring_triggered'] = array(
    'label' => t('Line item triggered'),
    'parameter' => array(
      'line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
    ),
    'group' => t('Commerce Recurring'),
    'callbacks' => array(
      'execute' => 'commerce_recurring_triggered',
    ),
  );

  return $actions;
}


/**
 * Rules action: loop over all items in view.
 */
function commerce_recurring_process_view($view_name) {
  $line_items = array ();
  $rows = views_get_view_result ($view_name, NULL, 'Monthly');
  foreach ($rows as $row) {

    // Filter out line items that have been created as part of the
    // recurring process. Can't do this in Views, bugger, so things will get
    // slower and slower.
    $original_line_item = $row->line_item_id == $row->commerce_recurring_last_trigger_line_item_id || $row->commerce_recurring_last_trigger_time == null;

    if ($original_line_item) {

      $last_trigger_time = $row->commerce_recurring_last_trigger_time ? $row->commerce_recurring_last_trigger_time : $row->commerce_order_commerce_line_item_created;
      // Only include line items for which on order has to
      // be generated.
      $thirty_one_days = 31 * 24 * 3600;
      $now = time();
      $d = getdate($now);
      $current_year = $d['year'];
      $current_month = $d['mon'];
      $current_day = $d['mday'];
      $last_day_of_the_month = intval (date('t'), $now);
      $d = getdate($last_trigger_time);
      $last_trigger_year = $d['year'];
      $last_trigger_month = $d['mon'];
      $last_trigger_day = $d['mday'];
      $to_trigger = ($now - $last_trigger_time > $thirty_one_days) || (($last_trigger_year != $current_year || $last_trigger_month != $current_month) && ($current_day == $last_day_of_the_month || $current_day <= $last_trigger_day));
      if ($to_trigger) {
        $line_item = commerce_line_item_load ($row->line_item_id);
        $line_items[] = $line_item;
      }
    }
  }
  return array('line_items_to_trigger' => $line_items);
}


/**
 * Rules action: create a new order duplicting the given line item on
 * the new order. Assumes type is product...
 */
function commerce_recurring_create_recurring_order($line_item) {
  // If cron hasn't been run for a while, we technically could create
  // multiple orders for every missing subscription period. But that
  // wouldn't be so far on the customer as he suddenly could be hit with a
  // very large bill. So we create just in order, and that for the last
  // month.

  // Get the original order
  $first_order = commerce_order_load ($line_item->order_id);

  // We'll just double check the calculation if an order should be
  // generated, based on the most accurate data in case there was a problem
  // with the view, and it simply returned every line item in the system.
  $last_trigger_time = db_query ('select time from {commerce_recurring_last_trigger} where line_item_id = :line_item_id', array ('line_item_id' => $line_item->line_item_id))->fetchField();
  $last_trigger_time = $last_trigger_time ? $last_trigger_time : $first_order->created;

  $thirty_one_days = 31 * 24 * 3600;
  $now = time();
  $d = getdate($now);
  $current_year = $d['year'];
  $current_month = $d['mon'];
  $current_day = $d['mday'];
  $last_day_of_the_month = intval (date('t'), $now);

  $d = getdate($last_trigger_time);
  $last_trigger_year = $d['year'];
  $last_trigger_month = $d['mon'];
  $last_trigger_day = $d['mday'];

  $to_trigger = ($now - $last_trigger_time > $thirty_one_days) || (($last_trigger_year != $current_year || $last_trigger_month != $current_month) && ($current_day == $last_day_of_the_month || $current_day <= $last_trigger_day));
  if ($to_trigger) {
    $year = $current_year;
    $month = $current_month;
    if ($current_month == 1) {
      $year--;
      $month = 12;
    }
    $new_order = _do_commerce_recurring_create_recurring_order ($line_item, $first_order, $year, $month, $last_trigger_day);

    // The next step in the checkout would be something like token
    // billing or sending an email

    return array ('new_order' => $new_order);
  }
  else
    return array();
}


function _do_commerce_recurring_create_recurring_order ($line_item, $first_order, $year, $month, $day) {
  // We basically follows the steps in commerce_cart_product_add(),
  // but we cannot use that function as it assumes a user has only one
  // order, and it would break the system if that particular user was just
  // shopping and we're creating another order here...

  $order_date =  mktime (0, 0, 0, $month, $day, $year);

  // Create the new order with the customer's uid
  $new_order = commerce_order_new ($first_order->uid);
  $new_order->commerce_customer_billing = $first_order->commerce_customer_billing;
  // Save it so it gets an order ID and return the full object.
  $new_order = commerce_order_save ($new_order);

  // Wrap the order for easy access to field data.
  $order_wrapper = entity_metadata_wrapper('commerce_order', $new_order);

  // Load and validate the specified product ID.
  $product_id = $line_item->commerce_product['und'][0]['product_id'];
  $product = commerce_product_load($product_id);

  // Create the new product line item.
  $new_line_item = commerce_product_line_item_new ($product, $line_item->quantity, $new_order->order_id);
  $new_line_item->line_item_label = t('Recurring !old_label for !month', array ('!old_label' => $new_line_item->line_item_label, '!month' => date ('F', $order_date)));

  // Process the unit price through Rules so it reflects the user's actual
  // purchase price.
  rules_invoke_event('commerce_product_calculate_sell_price', $new_line_item);

  // Save the line item now so we get its ID.
  $new_line_item = commerce_line_item_save($new_line_item);

  // Add it to the order's line item reference value.
  $order_wrapper->commerce_line_items[] = $new_line_item;

  // TODO: order should be marked as recurring so we don't include it next time!!, perhaps not make this a product type line item?

  // Save the updated order.
  commerce_order_save($new_order);

  // Make enabled payment methods available
  rules_invoke_all('commerce_payment_methods', $new_order);

  return $new_order;
}

/**
 * Rules action: all action(s) on line item completed succesfully
 */
function commerce_recurring_triggered($line_item) {
  commerce_recurring_save ($line_item->line_item_id);
}
