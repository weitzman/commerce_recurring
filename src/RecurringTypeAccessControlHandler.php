<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the recurring type entity.
 *
 * @see \Drupal\commerce_recurring\Entity\RecurringType
 */
class RecurringTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = AccessResult::allowedIfHasPermission($account, 'administer recurring types');

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = AccessResult::allowedIfHasPermission($account, 'administer recurring types');

    return $return_as_object ? $result : $result->isAllowed();
  }
}
