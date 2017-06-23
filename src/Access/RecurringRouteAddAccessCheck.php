<?php

namespace Drupal\commerce_Recurring\Access;

use Drupal\commerce_recurring\Entity\RecurringTypeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Determines access to for recurring pages.
 */
class RecurringRouteAddAccessCheck implements AccessInterface {

  /**
   * The recurring storage.
   *
   * @var \Drupal\commerce\CommerceContentEntityStorage
   */
  protected $recurringStorage;

  /**
   * The recurring access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $recurringAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a RecurringRouteAddAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->recurringStorage = $entity_type_manager->getStorage('commerce_recurring');
    $this->recurringAccess = $entity_type_manager->getAccessControlHandler('commerce_recurring');
  }

  /**
   * Checks create access for recurrings.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\commerce_recurring\Entity\RecurringTypeInterface $commerce_recurring_type
   *   (optional) The recurring type. If not specified, access is allowed if
   *   there exists at least one recurring type for which the user may create a
   *   recurring.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RecurringTypeInterface $commerce_recurring_type = NULL) {
    // If checking whether a recurring of a particular type may be created.
    if ($account->hasPermission('administer recurrings') || $account->hasPermission('create any recurring')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($commerce_recurring_type) {
      return $this->recurringAccess->createAccess($commerce_recurring_type->id(), $account, [], TRUE);
    }
    // If checking whether a recurring of any type may be created.
    foreach ($this->recurringStorage->loadMultiple() as $commerce_recurring_type) {
      if (($access = $this->recurringAccess->createAccess($commerce_recurring_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
