<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the recurring entity.
 *
 * @see \Drupal\commerce_recurring\Entity\Recurring
 */
class RecurringAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer recurrings') ||
      $account->hasPermission("$operation any recurring")
    ) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::access($entity, $operation, $account, TRUE);
    $result = $result->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    if ($account->hasPermission('administer recurrings') ||
      $account->hasPermission("create $entity_bundle recurring") ||
      $account->hasPermission('create any recurring')
    ) {
      $result = AccessResult::allowed()->cachePerPermissions();

      return $return_as_object ? $result : $result->isAllowed();
    }

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE);
    $result = $result->cachePerPermissions();

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $entity */
    $map = [
      'view' => 'view any recurring',
      'view_own' => 'view own any recurring',
      'update' => 'edit any recurring',
      'update_own' => 'edit own any recurring',
      'delete' => 'delete any recurring',
      'delete_own' => 'delete own any recurring',
    ];
    $bundle = $entity->bundle();
    $type_map = [
      "view_own_$bundle" => "view own $bundle recurring",
      "view_any_$bundle" => "view any $bundle recurring",
      "update_own_$bundle" => "edit own $bundle recurring",
      "update_any_$bundle" => "edit any $bundle recurring",
      "delete_own_$bundle" => "delete own $bundle recurring",
      "delete_any_$bundle" => "delete any $bundle recurring",
    ];

    // If there was no recurring to check against, or the $operation was not one
    // of the supported ones, we return access denied.
    if (!$entity || !isset($map[$operation]) || !isset($map["{$operation}_own"]) ||
      (!isset($type_map["{$operation}_own_$bundle"]) &&
        !isset($type_map["{$operation}_any_$bundle"]))
    ) {
      return AccessResult::forbidden();
    }

    // Statically cache access by recurring ID, user account ID and
    // operation.
    $cid = $entity->id() . ':' . $account->id() . ':' . $operation;

    if (!isset($this->accessCache[$cid])) {
      // Perform basic permission checks.
      if (!$account->hasPermission($map[$operation]) &&
        !$account->hasPermission($map["{$operation}_own"]) &&
        !$account->hasPermission($type_map["{$operation}_own_$bundle"]) &&
        !$account->hasPermission($type_map["{$operation}_any_$bundle"])
      ) {
        $this->accessCache[$cid] = FALSE;
      }
      elseif (($account->hasPermission($map["{$operation}_own"]) &&
        $account->id() != $entity->getOwnerId()) ||
        ($account->hasPermission($type_map["{$operation}_own_$bundle"]) &&
        $account->id() != $entity->getOwnerId())
      ) {
        $this->accessCache[$cid] = FALSE;
      }
      else {
        $this->accessCache[$cid] = TRUE;
      }
    }

    return AccessResult::allowedIf($this->accessCache[$cid])->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf(
      $account->hasPermission('create any recurring') ||
      $account->hasPermission('create ' . $entity_bundle . ' recurring') ||
        $account->hasPermission('create any ' . $entity_bundle . ' recurring')
      )->cachePerPermissions();
  }

}
