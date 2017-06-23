<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the recurring type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_recurring_type",
 *   label = @Translation("Recurring type"),
 *   label_singular = @Translation("Recurring type"),
 *   label_plural = @Translation("Recurring types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count recurring type",
 *     plural = "@count recurring types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_recurring\RecurringTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_recurring\Form\RecurringTypeForm",
 *       "edit" = "Drupal\commerce_recurring\Form\RecurringTypeForm",
 *       "delete" = "Drupal\commerce_recurring\Form\RecurringTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_recurring\RecurringTypeListBuilder",
 *   },
 *   admin_permission = "administer recurring types",
 *   config_prefix = "commerce_recurring_type",
 *   bundle_of = "commerce_recurring",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/recurring-types/add",
 *     "edit-form" = "/admin/commerce/config/recurring-types/{commerce_recurring_type}/edit",
 *     "delete-form" = "/admin/commerce/config/recurring-types/{commerce_recurring_type}/delete",
 *     "collection" = "/admin/commerce/config/recurring-types"
 *   }
 * )
 */
class RecurringType extends ConfigEntityBundleBase implements RecurringTypeInterface {

  /**
   * The recurring type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The recurring type label.
   *
   * @var string
   */
  protected $label;

}
