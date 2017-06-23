<?php

namespace Drupal\Tests\commerce_recurring\Functional;

use Drupal\Core\Url;

/**
 * Tests recurring UI behavior when there are no stores.
 *
 * @group commerce_recurring
 */
class RecurringNoStoreTest extends CommerceRecurringBrowserTestBase {

  /**
   * Tests creating a recurring.
   */
  public function testCreateRecurring() {
    $this->drupalGet('admin/commerce/recurrings');
    $this->clickLink('Create a new recurring');

    // Check that the warning is present.
    $session = $this->assertSession();
    $session->pageTextContains("Recurrings can't be created until a store has been added.");
    $session->linkExists('Add a new store.');
    $session->linkByHrefExists(Url::fromRoute('entity.commerce_store.add_page')->toString());
  }

}
