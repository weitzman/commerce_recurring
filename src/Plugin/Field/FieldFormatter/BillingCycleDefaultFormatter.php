<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Provides the default billing cycle formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_billing_cycle_default",
 *   module = "commerce_recurring",
 *   label = @Translation("Billing cycle"),
 *   field_types = {
 *     "commerce_billing_cycle"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class BillingCycleDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingCycleItem $item */
    foreach ($items as $delta => $item) {
      $billing_cycle = $item->toBillingCycle();
      $start_date = $billing_cycle->getStartDate()->format('M jS Y H:i:s');
      $end_date = $billing_cycle->getEndDate()->format('M jS Y H:i:s');

      $build[$delta] = [
        '#plain_text' => $start_date . ' - ' . $end_date,
      ];
    }
    return $build;
  }

}
