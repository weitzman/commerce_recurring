<?php
/*
 * @file commerce_recurring.rules_defaults.inc
 * Provides default rules configurations for commerce recurring
 * @copyright Copyright(c) 2011 Lee Rowlands
 * @license GPL v2 http://www.fsf.org/licensing/licenses/gpl.html
 * @author Lee Rowlands contact at rowlandsgroup dot com
 * 
 */
/**
 * Implementation of hook_default_rules_configuration().
 */
function commerce_recurring_default_rules_configuration() {
  $items = array();
  $items['commerce_recurring_process_orders'] = entity_import('rules_config', '{ "commerce_recurring_process_orders" : {
      "LABEL" : "Generate Recurring Orders",
      "PLUGIN" : "reaction rule",
      "TAGS" : [ "Commerce Recurring" ],
      "REQUIRES" : [ "commerce_recurring", "rules" ],
      "ON" : [ "cron" ],
      "DO" : [
        { "commerce_recurring_load_recurring" : { "PROVIDE" : { "orders" : { "orders" : "Recurring Orders" } } } },
        { "LOOP" : {
            "USING" : { "list" : [ "orders" ] },
            "ITEM" : { "order" : "Order" },
            "DO" : [
              { "component_commerce_recurring_generate_orders" : { "order" : [ "order" ] } }
            ]
          }
        }
      ]
    }
  }');
  $items['commerce_recurring_generate_orders'] = entity_import('rules_config', '{ "commerce_recurring_generate_orders" : {
      "LABEL" : "Generate Recurring Order if Appropriate",
      "PLUGIN" : "rule",
      "REQUIRES" : [ "commerce_recurring" ],
      "USES VARIABLES" : { "order" : { "label" : "Order", "type" : "commerce_order" } },
      "IF" : [
        { "commerce_recurring_rules_order_contains_recurring" : { "commerce_order" : [ "order" ] } },
        { "commerce_recurring_rules_order_is_master" : { "commerce_order" : [ "order" ] } }
      ],
      "DO" : [
        { "commerce_recurring_process_recurring" : {
            "USING" : { "commerce_order" : [ "order" ] },
            "PROVIDE" : { "recurring_order" : { "recurring_order" : "New Order" } }
          }
        }
      ]
    }
  }');
  return $items;
}
