<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\commerce_recurring\BillingCycle;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'commerce_price' field type.
 *
 * @FieldType(
 *   id = "commerce_billing_cycle",
 *   label = @Translation("Billing cycle"),
 *   description = @Translation("Stores a a billing cycle"),
 *   category = @Translation("Commerce"),
 *   default_widget = "commerce_billing_cycle_default",
 *   default_formatter = "commerce_billing_cycle_default",
 * )
 */
class BillingCycleItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['starts'] = DataDefinition::create('timestamp')
      ->setLabel(t('Start date'))
      ->setRequired(TRUE);
    $properties['ends'] = DataDefinition::create('timestamp')
      ->setLabel(t('End date'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'starts' => [
          'type' => 'int',
        ],
        'ends' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'range' => ['starts', 'ends'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->starts) || empty($this->ends);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Allow callers to pass a BillingCycle value object.
    if ($values instanceof BillingCycle) {
      $values = [
        'starts' => $values->getStartDate(),
        'ends' => $values->getEndDate(),
      ];
    }

    // DrupalDateTime values passed by the caller or taken via BillingCycle.
    if (isset($values['starts']) && ($values['starts'] instanceof DrupalDateTime)) {
      $values['starts']->setTimezone(new \DateTimezone('UTC'));
      $values['starts'] = $values['starts']->format('U');
    }
    if (isset($values['ends']) && ($values['ends'] instanceof DrupalDateTime)) {
      $values['ends']->setTimezone(new \DateTimezone('UTC'));
      $values['ends'] = $values['ends']->format('U');
    }

    parent::setValue($values, $notify);
  }

  /**
   * Gets the billing cycle value object for the current field item.
   *
   * @return \Drupal\commerce_recurring\BillingCycle
   *   The billing cycle object.
   */
  public function toBillingCycle() {
    // @todo Set the timezones on both DrupalDateTime objects.
    return new BillingCycle($this->get('starts')->getDateTime(), $this->get('ends')->getDateTime());
  }

}
