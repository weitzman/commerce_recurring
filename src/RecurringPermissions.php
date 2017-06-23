<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_recurring\Entity\RecurringType;

/**
 * Provides dynamic permissions for commerce recurring of different types.
 */
class RecurringPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of commerce recurring type permissions.
   *
   * @return array
   *   The commerce recurring type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function recurringTypePermissions() {
    $perms = [
      'create any recurring' => [
        'title' => $this->t('Create new recurring of any type'),
        'description' => $this->t('Allows users to create recurrings of any type.'),
      ],
      'view any recurring' => [
        'title' => $this->t('View recurring of any type'),
        'description' => $this->t('Allows users to view recurrings of any type.'),
      ],
      'update any recurring' => [
        'title' => $this->t('Edit recurring of any type'),
        'description' => $this->t('Allows users to edit recurrings of any type.'),
      ],
      'delete any recurring' => [
        'title' => $this->t('Delete recurring of any type'),
        'description' => $this->t('Allows users to delete recurrings of any type.'),
      ],
      'view own any recurring' => [
        'title' => $this->t('View own recurring of any type'),
        'description' => $this->t('Allows users to viewown  recurrings of any type.'),
      ],
      'update own any recurring' => [
        'title' => $this->t('Edit own recurring of any type'),
        'description' => $this->t('Allows users to edit own recurrings of any type.'),
      ],
      'delete own any recurring' => [
        'title' => $this->t('Delete own recurring of any type'),
        'description' => $this->t('Allows users to delete own recurrings of any type.'),
      ],
    ];

    // Generate commerce recurring permissions for all commerce recurring
    // types.
    foreach (RecurringType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of commerce recurring permissions for a given commerce recurring type.
   *
   * @param \Drupal\commerce_recurring\Entity\RecurringType $type
   *   The commerce recurring type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(RecurringType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id recurring" => [
        'title' => $this->t('%type_name: Create new recurring', $type_params),
        'description' => $this->t('Allows users to create %type_name recurrings.', $type_params),
      ],
      "view own $type_id recurring" => [
        'title' => $this->t('%type_name: View own recurring', $type_params),
        'description' => $this->t('Allows users to view own %type_name recurrings.', $type_params),
      ],
      "view any $type_id recurring" => [
        'title' => $this->t('%type_name: View any recurring', $type_params),
        'description' => $this->t('Allows users to  view any %type_name recurrings.', $type_params),
      ],
      "edit own $type_id recurring" => [
        'title' => $this->t('%type_name: Edit own recurring', $type_params),
        'description' => $this->t('Allows users to edit own %type_name recurrings.', $type_params),
      ],
      "edit any $type_id recurring" => [
        'title' => $this->t('%type_name: Edit any recurring', $type_params),
        'description' => $this->t('Allows users to  edit any %type_name recurrings.', $type_params),
      ],
      "delete own $type_id recurring" => [
        'title' => $this->t('%type_name: Delete own recurring', $type_params),
        'description' => $this->t('Allows users to delete own %type_name recurrings.', $type_params),
      ],
      "delete any $type_id recurring" => [
        'title' => $this->t('%type_name: Delete any recurring', $type_params),
        'description' => $this->t('Allows users to delete any %type_name recurrings.', $type_params),
      ],
    ];
  }

}
