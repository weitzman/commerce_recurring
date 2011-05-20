<?php

/**
 * Export Drupal Commerce Recurring to Views.
 */

/**
 * Implements hook_views_data().
 */
function commerce_recurring_views_data() {
  $data = array();

  $data['commerce_recurring_last_trigger']['table']['group']  = t('Commerce Recurring');

  // We'll make this a base table as I couldn't figure out how to join
  // it to line item.
  $data['commerce_recurring_last_trigger']['table']['base'] = array(
    'field' => 'line_item_id',
    'title' => t('Commerce Recurring'),
    'help' => t('A line item referenced by another entity.'),
  );

  $data['commerce_recurring_last_trigger']['table']['join'] = array(
    'commerce_line_item' => array(
      'left_field' => 'line_item_id',
      'field' => 'line_item_id',
    ),
  );

  // Expose the line item ID.
  $data['commerce_recurring_last_trigger']['line_item_id'] = array(
    'title' => t('Line item ID'),
    'help' => t('The reference to the last trigger time for a line item.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'commerce_line_item_handler_argument_line_item_line_item_id',
      'name field' => 'line_item_label',
      'numeric' => TRUE,
      'validate type' => 'line_item_id',
    ),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'commerce_line_item',
      'field' => 'line_item_id',
      'label' => t('Last trigger time', array(), array('context' => 'a drupal commerce line item trigger')),
    ),
  );

  // Expose the last triggered timestamp.
  $data['commerce_recurring_last_trigger']['time'] = array(
    'title' => t('Last trigger date'),
    'help' => t('The date the line item was last triggered.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  return $data;
}