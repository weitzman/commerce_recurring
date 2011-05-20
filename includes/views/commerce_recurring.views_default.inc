<?php

/**
 * Views for line item recurring trigger displays.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_recurring_views_default_views() {
  $views = array();

  // Line item view for recurring (payment) trigger
  $view = new view;
  $view->name = 'recurring_line_items';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'commerce_line_item';
  $view->human_name = 'Recurring line items';
  $view->core = 7;
  $view->api_version = '3.0-alpha1';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Recurring line items';
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  /* Relationship: Commerce Line Item: Order ID */
  $handler->display->display_options['relationships']['order_id']['id'] = 'order_id';
  $handler->display->display_options['relationships']['order_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['relationships']['order_id']['field'] = 'order_id';
  $handler->display->display_options['relationships']['order_id']['required'] = 1;
  /* Relationship: Line item: Referenced product */
  $handler->display->display_options['relationships']['commerce_product_product_id']['id'] = 'commerce_product_product_id';
  $handler->display->display_options['relationships']['commerce_product_product_id']['table'] = 'field_data_commerce_product';
  $handler->display->display_options['relationships']['commerce_product_product_id']['field'] = 'commerce_product_product_id';
  $handler->display->display_options['relationships']['commerce_product_product_id']['required'] = 1;
  /* Relationship: Commerce Recurring: Line item ID */
  $handler->display->display_options['relationships']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['relationships']['line_item_id']['table'] = 'commerce_recurring_last_trigger';
  $handler->display->display_options['relationships']['line_item_id']['field'] = 'line_item_id';
  $handler->display->display_options['relationships']['line_item_id']['required'] = 0;
  /* Field: Commerce Order: Order ID */
  $handler->display->display_options['fields']['order_id']['id'] = 'order_id';
  $handler->display->display_options['fields']['order_id']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['order_id']['field'] = 'order_id';
  $handler->display->display_options['fields']['order_id']['relationship'] = 'order_id';
  $handler->display->display_options['fields']['order_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['order_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['order_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['order_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['order_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['order_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['order_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['order_id']['empty_zero'] = 0;
  /* Field: Commerce Line Item: Line item ID */
  $handler->display->display_options['fields']['line_item_id']['id'] = 'line_item_id';
  $handler->display->display_options['fields']['line_item_id']['table'] = 'commerce_line_item';
  $handler->display->display_options['fields']['line_item_id']['field'] = 'line_item_id';
  /* Field: Product: Price */
  $handler->display->display_options['fields']['commerce_price']['id'] = 'commerce_price';
  $handler->display->display_options['fields']['commerce_price']['table'] = 'field_data_commerce_price';
  $handler->display->display_options['fields']['commerce_price']['field'] = 'commerce_price';
  $handler->display->display_options['fields']['commerce_price']['relationship'] = 'commerce_product_product_id';
  $handler->display->display_options['fields']['commerce_price']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['external'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['commerce_price']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['commerce_price']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['commerce_price']['alter']['html'] = 0;
  $handler->display->display_options['fields']['commerce_price']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['commerce_price']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['commerce_price']['hide_empty'] = 0;
  $handler->display->display_options['fields']['commerce_price']['empty_zero'] = 0;
  $handler->display->display_options['fields']['commerce_price']['click_sort_column'] = 'amount';
  $handler->display->display_options['fields']['commerce_price']['type'] = 'commerce_price_raw_amount';
  $handler->display->display_options['fields']['commerce_price']['field_api_classes'] = 0;
  /* Field: Commerce Order: Updated date */
  $handler->display->display_options['fields']['changed']['id'] = 'changed';
  $handler->display->display_options['fields']['changed']['table'] = 'commerce_order';
  $handler->display->display_options['fields']['changed']['field'] = 'changed';
  $handler->display->display_options['fields']['changed']['relationship'] = 'order_id';
  $handler->display->display_options['fields']['changed']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['external'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['changed']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['changed']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['changed']['alter']['html'] = 0;
  $handler->display->display_options['fields']['changed']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['changed']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['changed']['hide_empty'] = 0;
  $handler->display->display_options['fields']['changed']['empty_zero'] = 0;
  $handler->display->display_options['fields']['changed']['date_format'] = 'long';
  /* Field: Commerce Recurring: Last trigger date */
  $handler->display->display_options['fields']['time']['id'] = 'time';
  $handler->display->display_options['fields']['time']['table'] = 'commerce_recurring_last_trigger';
  $handler->display->display_options['fields']['time']['field'] = 'time';
  $handler->display->display_options['fields']['time']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['time']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['time']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['time']['alter']['external'] = 0;
  $handler->display->display_options['fields']['time']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['time']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['time']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['time']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['time']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['time']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['time']['alter']['html'] = 0;
  $handler->display->display_options['fields']['time']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['time']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['time']['hide_empty'] = 0;
  $handler->display->display_options['fields']['time']['empty_zero'] = 0;
  $handler->display->display_options['fields']['time']['date_format'] = 'time ago';
  /* Contextual filter: Product: Recurring (field_recurring) */
  $handler->display->display_options['arguments']['field_recurring_frequency']['id'] = 'field_recurring_frequency';
  $handler->display->display_options['arguments']['field_recurring_frequency']['table'] = 'field_data_field_recurring';
  $handler->display->display_options['arguments']['field_recurring_frequency']['field'] = 'field_recurring_frequency';
  $handler->display->display_options['arguments']['field_recurring_frequency']['relationship'] = 'commerce_product_product_id';
  $handler->display->display_options['arguments']['field_recurring_frequency']['default_action'] = 'empty';
  $handler->display->display_options['arguments']['field_recurring_frequency']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['field_recurring_frequency']['default_argument_skip_url'] = 0;
  $handler->display->display_options['arguments']['field_recurring_frequency']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['field_recurring_frequency']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['field_recurring_frequency']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['field_recurring_frequency']['glossary'] = 0;
  $handler->display->display_options['arguments']['field_recurring_frequency']['limit'] = '0';
  $handler->display->display_options['arguments']['field_recurring_frequency']['transform_dash'] = 0;
  $handler->display->display_options['arguments']['field_recurring_frequency']['break_phrase'] = 0;
  /* Filter criterion: Commerce Line Item: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'commerce_line_item';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
                                                                         'product' => 'product',
                                                                         );
  /* Filter criterion: Commerce Order: Order status */
  $handler->display->display_options['filters']['status']['id'] = 'status';
  $handler->display->display_options['filters']['status']['table'] = 'commerce_order';
  $handler->display->display_options['filters']['status']['field'] = 'status';
  $handler->display->display_options['filters']['status']['relationship'] = 'order_id';
  $handler->display->display_options['filters']['status']['value'] = array(
                                                                           'completed' => 'completed',
                                                                           );

  /* Display: Recurring line items */
  $handler = $view->new_display('page', 'Recurring line items', 'recurring_line_items');
  $handler->display->display_options['path'] = 'admin/commerce/recurring/list';
  $translatables['recurring_line_items'] = array(
    t('Master'),
    t('Recurring line items'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('Order'),
    t('Product'),
    t('Last trigger time'),
    t('Order ID'),
    t('Line item ID'),
    t('Price'),
    t('Updated date'),
    t('Last trigger date'),
    t('All'),
  );
}