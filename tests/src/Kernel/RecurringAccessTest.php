<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\Entity\RecurringInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the access control handler for the recurring entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\RecurringAccessControlHandler
 *
 * @group commerce_recurring
 */
class RecurringAccessTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'options',
    'path',
    'entity',
    'views',
    'address',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'commerce_order',
    'interval',
    'commerce_recurring',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_line_item');
    $this->installEntitySchema('commerce_recurring');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_store');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_recurring');

    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('commerce_recurring');

    $this->entityStorage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_recurring');

    // Create a second recurring type.
    $this->container->get('entity_type.manager')
      ->getStorage('commerce_recurring_type')
      ->create(['type' => 'sample']);

    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->createUser();
  }

  /**
   * Runs basic tests for access control handler for the recurring entity.
   */
  public function testRecurringAccess() {
    // Ensures user without permission can do nothing.
    $web_user1 = $this->createUser();
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring1 */
    $recurring1 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringCreateAccess($recurring1->bundle(), FALSE, $web_user1);
    $this->assertRecurringAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring1, $web_user1);

    // Ensures user with 'administer recurrings' permission can do everything.
    $web_user2 = $this->createUser([], ['administer recurrings']);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring2 */
    $recurring2 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringCreateAccess($recurring2->bundle(), TRUE, $web_user2);
    $this->assertRecurringAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring2, $web_user2);

    // Ensures user with '__EVERY_OP__ any recurring' permission can do
    // everything.
    $web_user3 = $this->createUser([], [
      'create any recurring',
      'view any recurring',
      'edit any recurring',
      'delete any recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring3 */
    $recurring3 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringCreateAccess($recurring3->bundle(), TRUE, $web_user3);
    $this->assertRecurringAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring3, $web_user3);

    // Ensures user with '(view|edit|delete) own any recurring' permission can
    // do everything if it is the owner.
    $web_user4 = $this->createUser([], [
      'view own any recurring',
      'edit own any recurring',
      'delete own any recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring4 */
    $recurring4 = $this->entityStorage->create(['type' => 'default']);
    $recurring4->setOwner($web_user4);
    $this->assertRecurringAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring4, $web_user4);

    // Ensures user with '(view|edit|delete) own any recurring' permission can
    // do nothing if it is not the owner.
    $web_user5 = $this->createUser([], [
      'view own any recurring',
      'edit own any recurring',
      'delete own any recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring5 */
    $recurring5 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring5, $web_user5);

    // Ensures user with '__EVERY_OP__ (NULL|any) default recurring' permission
    // can do everything.
    $web_user6 = $this->createUser([], [
      'create default recurring',
      'view any default recurring',
      'edit any default recurring',
      'delete any default recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring6 */
    $recurring6 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringCreateAccess($recurring6->bundle(), TRUE, $web_user6);
    $this->assertRecurringAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring6, $web_user6);

    // Ensures user with '__EVERY_OP__ (NULL|any) default recurring' permission
    // can do nothing if the type is not the default one.
    $web_user7 = $this->createUser([], [
      'create default recurring',
      'view any default recurring',
      'edit any default recurring',
      'delete any default recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring7 */
    $recurring7 = $this->entityStorage->create(['type' => 'sample']);
    $this->assertRecurringCreateAccess('sample', FALSE, $web_user7);
    $this->assertRecurringAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring7, $web_user7);

    // Ensures user with '(view|edit|delete) own default recurring' permission
    // can do everything if it is the owner.
    $web_user8 = $this->createUser([], [
      'view own default recurring',
      'edit own default recurring',
      'delete own default recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring8 */
    $recurring8 = $this->entityStorage->create(['type' => 'default']);
    $recurring8->setOwner($web_user8);
    $this->assertRecurringAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring8, $web_user8);

    // Ensures user with '(view|edit|delete) own default recurring' permission
    // can do nothing if it is not the owner.
    $web_user9 = $this->createUser([], [
      'view own default recurring',
      'edit own default recurring',
      'delete own default recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring9 */
    $recurring9 = $this->entityStorage->create(['type' => 'default']);
    $this->assertRecurringAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring9, $web_user9);


    // Ensures user with '(view|edit|delete) own default recurring' permission
    // can do nothing if the type is not the default one.
    $web_user9 = $this->createUser([], [
      'view own default recurring',
      'edit own default recurring',
      'delete own default recurring',
    ]);
    /** @var \Drupal\commerce_recurring\Entity\RecurringInterface $recurring9 */
    $recurring9 = $this->entityStorage->create(['type' => 'sample']);
    $recurring9->setOwner($web_user9);
    $this->assertRecurringAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring9, $web_user9);
  }

  /**
   * Asserts that recurring access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected recurring access grants for the
   *   recurring and account, with each key as the name of an operation
   *   (e.g. 'view', 'delete') and each value a Boolean indicating whether
   *   access to that operation should be granted.
   * @param \Drupal\commerce_recurring\Entity\RecurringInterface $recurring
   *   The recurring object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertRecurringAccess(array $ops, RecurringInterface $recurring, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals(
        $result,
        $this->accessHandler->access($recurring, $op, $account),
        $this->recurringAccessAssertMessage($op, $result, $recurring->language()
          ->getId())
      );
    }
  }

  /**
   * Asserts that recurring create access correctly grants or denies access.
   *
   * @param string $bundle
   *   The recurring bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the
   *   recurring to check. If NULL, the untranslated (fallback) access is
   *   checked.
   */
  public function assertRecurringCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals(
      $result,
      $this->accessHandler->createAccess($bundle, $account, ['langcode' => $langcode]),
      $this->recurringAccessAssertMessage('create', $result, $langcode)
    );
  }

  /**
   * Constructs an assert message to display which recurring access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the
   *   recurring to check. If NULL, the untranslated (fallback) access is
   *   checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the recurring access permission test that was performed.
   */
  public function recurringAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Recurring access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}
