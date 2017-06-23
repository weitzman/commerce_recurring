<?php

namespace Drupal\Tests\commerce_recurring\Kernel;

use Drupal\commerce_recurring\Entity\RecurringTypeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the access control handler for the recurring type entity.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\RecurringAccessControlHandler
 *
 * @group commerce_recurring
 */
class RecurringTypeAccessTest extends EntityKernelTestBase {

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
      ->getAccessControlHandler('commerce_recurring_type');

    $this->entityStorage = $this->container->get('entity_type.manager')
      ->getStorage('commerce_recurring_type');

    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->createUser();
  }

  /**
   * Runs basic tests for access control handler for the recurring type entity.
   */
  public function testRecurringTypeAccess() {
    /** @var \Drupal\commerce_recurring\Entity\RecurringTypeInterface $recurring_type */
    $recurring_type = $this->entityStorage->create([]);

    // Ensures user without 'administer recurring types' permission can do
    // nothing.
    $web_user1 = $this->createUser();
    $this->assertRecurringTypeCreateAccess($recurring_type->bundle(), FALSE, $web_user1);
    $this->assertRecurringTypeAccess([
      'view' => FALSE,
      'update' => FALSE,
      'delete' => FALSE,
    ], $recurring_type, $web_user1);

    // Ensures user with 'administer recurring types' permission can do
    // everything.
    $web_user2 = $this->createUser([], ['administer recurring types']);
    $this->assertRecurringTypeCreateAccess($recurring_type->bundle(), TRUE, $web_user2);
    $this->assertRecurringTypeAccess([
      'view' => TRUE,
      'update' => TRUE,
      'delete' => TRUE,
    ], $recurring_type, $web_user2);
  }

  /**
   * Asserts that recurring type access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected recurring type access grants for the
   *   recurring type and account, with each key as the name of an operation
   *   (e.g. 'view', 'delete') and each value a Boolean indicating whether
   *   access to that operation should be granted.
   * @param \Drupal\commerce_recurring\Entity\RecurringTypeInterface $recurring_type
   *   The recurring type object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertRecurringTypeAccess(array $ops, RecurringTypeInterface $recurring_type, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals(
        $result,
        $this->accessHandler->access($recurring_type, $op, $account),
        $this->recurringTypeAccessAssertMessage($op, $result, $recurring_type->language()
          ->getId())
      );
    }
  }

  /**
   * Asserts that recurring type create access correctly grants or denies access.
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
  public function assertRecurringTypeCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEquals(
      $result,
      $this->accessHandler->createAccess($bundle, $account, ['langcode' => $langcode]),
      $this->recurringTypeAccessAssertMessage('create', $result, $langcode)
    );
  }

  /**
   * Constructs an assert message to display which recurring type access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the
   *   recurring type to check. If NULL, the untranslated (fallback) access is
   *   checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the recurring type access permission test that was performed.
   */
  public function recurringTypeAccessAssertMessage($operation, $result, $langcode = NULL) {
    return new FormattableMarkup(
      'Recurring type access returns @result with operation %op, language code %langcode.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty',
      ]
    );
  }

}
