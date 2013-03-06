<?php

/**
 * @file
 * Provides metadata for the commerce recurring entity.
 */

/**
 * Implements hook_entity_property_info().
 */
function commerce_recurring_entity_property_info() {
  $info = array();

  // Add meta-data about the basic commerce_product properties.
  $properties = &$info['commerce_recurring']['properties'];

  $properties['commerce_recurring_id'] = array(
    'label' => t('Commerce recurring ID'),
    'description' => t('The internal numeric ID of the commerce recurring entity.'),
    'type' => 'integer',
    'schema field' => 'commerce_recurring_id',
  );
  $properties['type'] = array(
    'label' => t('Type'),
    'description' => t('The type of the commerce recurring entity.'),
    'type' => 'token',
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    //'options list' => 'commerce_product_type_options_list',
    'required' => TRUE,
    'schema field' => 'type',
  );
  $properties['status'] = array(
    'label' => t('Status'),
    'description' => t('Boolean indicating whether the commerce recurring entity is active or disabled.'),
    'type' => 'boolean',
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    'schema field' => 'status',
  );
  $properties['start_date'] = array(
    'label' => t('Commerce recurring start date'),
    'description' => t('The date the commerce recurring entity was created.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    'schema field' => 'start_date',
  );
  $properties['due_date'] = array(
    'label' => t('Commerce recurring due date'),
    'description' => t('The date when the commerce recurring has the next due.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    'schema field' => 'due_date',
  );
  $properties['end_date'] = array(
    'label' => t('Commerce recurring end date'),
    'description' => t('The date when the commerce recurring ends.'),
    'type' => 'date',
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    'schema field' => 'end_date',
  );
  $properties['uid'] = array(
    'label' => t('Creator ID'),
    'type' => 'integer',
    'description' => t('The unique ID of the commerce recurring entity creator.'),
    'setter callback' => 'entity_property_verbatim_set',
    //'setter permission' => 'administer commerce_product entities',
    'clear' => array('creator'),
    'schema field' => 'uid',
  );
  $properties['creator'] = array(
    'label' => t('Creator'),
    'type' => 'user',
    'description' => t('The creator of the commerce recurring entity.'),
    'getter callback' => 'commerce_product_get_properties',
    'setter callback' => 'commerce_product_set_properties',
    //'setter permission' => 'administer commerce_product entities',
    'required' => TRUE,
    'computed' => TRUE,
    'clear' => array('uid'),
  );

  return $info;
}
