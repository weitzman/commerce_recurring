<?php

/**
 * @file
 * Default rule configurations for Commerce recurring.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function commerce_recurring_default_rules_configuration() {
  $rules = array();

  $rule = rules_reaction_rule();
  $rule->label = t('Override price for recurring');
  $rule->active = TRUE;
  $rule
    ->event('commerce_product_calculate_sell_price')
    ->condition('entity_has_field', array(
      'entity:select' => 'commerce-line-item',
      'field' => 'commerce_product',
    ))
    ->condition('entity_has_field', array(
      'entity:select' => 'commerce-line-item:commerce-product',
      'field' => 'commerce_recurring_ini_price',
    ))
    ->condition('entity_has_field', array(
      'entity:select' => 'commerce-line-item:commerce-product',
      'field' => 'commerce_recurring_rec_price',
    ))
    ->action('commerce_recurring_set_price', array(
      'commerce_line_item:select' => 'commerce-line-item',
      'listing_price:select' => 'commerce-line-item:commerce_product:commerce-recurring-ini-price',
      'initial_price:select' => 'commerce-line-item:commerce_product:commerce-recurring-ini-price',
      'recurring_price:select' => 'commerce-line-item:commerce_product:commerce-recurring-rec-price',
    ));

  $rule->weight = -10;

  $rules['commerce_recurring_override_price'] = $rule;

  $rule = rules_reaction_rule();
  $rule->label = t('Create a recurring entity when the order is completed');
  $rule->active = TRUE;
  $rule
    ->event('commerce_payment_order_paid_in_full')
    ->action('commerce_recurring_get_recurring_line_items', array(
      'commerce_order:select' => 'commerce-order',
      'provides' => array('commerce_line_items', 'commerce_line_item'),
    ));

  $loop = rules_loop(array(
    'list:select' => 'commerce-line-items',
    'item:var' => 'current_line_item',
    'item:label' => t('Current line item'),
  ))->action('commerce_recurring_generate_recurring_product', array(
    'commerce_order:select' => 'commerce-order',
    'commerce_line_item:select' => 'current-line-item',
  ));
  $rule->action($loop);

  $rules['commerce_recurring_generate_recurring_product'] = $rule;

  $rule = rules_reaction_rule();
  $rule->label = t('Generate recurring orders on cron run');
  $rule->active = TRUE;
  $rule
    ->event('cron')
    ->action('commerce_recurring_get_due_items', array(
      'number_items' => 5,
      'timestamp:select' => 'site:current-date',
      'provides' => array('commerce_recurring_entities', 'commerce_recurring_entity'),
    ));

  $loop = rules_loop(array(
    'list:select' => 'commerce-recurring-entities',
    'item:var' => 'current_recurring_entity',
    'item:label' => t('Current recurring entity'),
  ))->action('commerce_recurring_generate_order_from_recurring', array(
    'commerce_recurring:select' => 'current-recurring-entity',
    'timestamp:select' => 'site:current-date',
  ));
  $rule->action($loop);

  $rules['commerce_recurring_cron_generate_orders'] = $rule;

  return $rules;
}
