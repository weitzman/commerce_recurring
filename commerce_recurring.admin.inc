<?php
/*
 * @file commerce_recurring.admin.inc
 * Provides admin form for commerce_recurring
 * @copyright Copyright(c) 2011 Lee Rowlands
 * @license GPL v2 http://www.fsf.org/licensing/licenses/gpl.html
 * @author Lee Rowlands contact at rowlandsgroup dot com
 * 
 */

/**
 * Form builder to display admin form
*/
function commerce_recurring_admin_form($form, $form_state) {
  $form = array();
  
  $form['commerce_recurring_batch_process'] = array(
    '#type' => 'select',
    '#options' => drupal_map_assoc(range(1,50)),
    '#title' => t('Batch size'),
    '#description' => t('Recurring orders are process in batches of this size.'),
    '#default_value' => variable_get('commerce_recurring_batch_process', 20)
  );
  
  $form['commerce_recurring_payment_interval'] = array(
    '#type' => 'select',
    '#field_suffix' => t(' days'),
    '#options' => drupal_map_assoc(range(0,30)),
    '#title' => t('Payment due'),
    '#description' => t('Payments for recurring orders are due this many days after the order is created.'),
    '#default_value' => variable_get('commerce_recurring_payment_interval', 20)
  );
  
  return system_settings_form($form);
}