<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'commerce_line_item_table' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_recurring_line_item_table",
 *   label = @Translation("Recurring line item table"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class RecurringLineItemTable extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $recurring = $items->getEntity();
    return [
      '#type' => 'view',
      // @todo Allow the view to be configurable.
      '#name' => 'commerce_recurring_line_item_table',
      '#arguments' => [$recurring->id()],
      '#embed' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();

    return $entity_type == 'commerce_recurring' && $field_name == 'line_items';
  }

}
