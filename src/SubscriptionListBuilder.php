<?php

namespace Drupal\commerce_recurring;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for subscriptions.
 */
class SubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Subscription');
    $header['customer'] = $this->t('Customer');
    $header['state'] = $this->t('State');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_recurring\Entity\SubscriptionInterface */
    $row = [
      'label' => $entity->label(),
      'customer' => $entity->getCustomer()->getDisplayName(),
      'state' => $entity->getState()->getLabel(),
    ];

    return $row + parent::buildRow($entity);
  }

}
