<?php

/**
 * Rules integration for recurring triggers.
 */

/**
 * Implements hook_rules_action_info().
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
        'type' => 'list<commerce_line_item>', // What we actually provide is the view row, not sure how to deal with that
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
 * Rules action: all action(s) on line item completed succesfully
 */
function commerce_recurring_process_view($view_name) {
  $line_items = array ();
  $rows = views_get_view_result ($view_name, NULL, 'Monthly');
  foreach ($rows as $row) {
    $last_trigger_time = $row->commerce_recurring_last_trigger_time ? $row->commerce_recurring_last_trigger_time : $row->commerce_order_commerce_line_item_changed;
    $line_item = commerce_line_item_load ($row->line_item_id);
    $line_items[] = $line_item;
  }
  return array('line_items_to_trigger' => $line_items);
}


/**
 * Rules action: all action(s) on line item completed succesfully
 */
function commerce_recurring_triggered($line_item) {
  commerce_recurring_save ($line_item->line_item_id);
}
