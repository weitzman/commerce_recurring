<?php

namespace Drupal\commerce_recurring;

use Drupal\commerce_recurring\Entity\RecurringType;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for recurrings.
 */
class RecurringListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new RecurringListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter, RedirectDestinationInterface $redirect_destination, CurrentRouteMatch $current_route_match) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type_manager) {
    return new static(
      $entity_type_manager,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'recurring_id' => [
        'data' => $this->t('Recurring ID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'customer' => [
        'data' => $this->t('Customer'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => [
        'data' => $this->t('Status'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'start_date' => [
        'data' => $this->t('Start date'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'due_date' => [
        'data' => $this->t('Due date'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'end_date' => [
        'data' => $this->t('End date'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_recurring\Entity\Recurring */
    $recurringType = RecurringType::load($entity->bundle());

    $row = [
      'recurring_id' => $entity->id(),
      'type' => $recurringType->label(),
      'customer' => [
        'data' => [
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ],
      ],
      'status' => $entity->isEnabled() ? t('Enabled') : t('Disabled'),
      'created' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
      'start_date' => $this->dateFormatter->format($entity->getStartDateTime(), 'short'),
      'due_date' => $this->dateFormatter->format($entity->getDueDateTime(), 'short'),
      'end_date' => $this->dateFormatter->format($entity->getEndDateTime(), 'short'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $recurring) {
    /* @var \Drupal\commerce_recurring\Entity\Recurring $recurring */
    $operations = parent::getDefaultOperations($recurring);

    $destination = $this->redirectDestination->getAsArray();
    $current_route = $this->currentRouteMatch->getRouteObject();

    if ($recurring->access('update') && $recurring->hasLinkTemplate('reassign-form')) {
      $operations['reassign'] = [
        'title' => $this->t('Reassign'),
        'weight' => 20,
        'url' => $recurring->toUrl('reassign-form'),
      ];
    }

    if ($recurring->access('update') &&
      $recurring->getOwnerId() == \Drupal::currentUser()->id() &&
      // @todo Nor sure if we must hide if user has administer permissions.
      !\Drupal::currentUser()->hasPermission('administer recurrings') &&
      ($current_route && strpos($current_route->getPath(), '/user/{user}') === 0) &&
      $recurring->hasLinkTemplate('user-edit-form')
    ) {
      $operations['user-edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 0,
        'url' => Url::fromRoute('entity.commerce_recurring.user_edit_form',
          [
            'user' => $recurring->getOwnerId(),
            'commerce_recurring' => $recurring->id(),
          ]
        ),
      ];
    }

    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    return $operations;
  }

}
