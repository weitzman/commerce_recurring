<?php

namespace Drupal\commerce_recurring;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of recurring usage group plugins.
 *
 * @see \Drupal\commerce_recurring\Annotation\CommerceRecurringUsageGroup
 * @see plugin_api
 */
class RecurringUsageGroupManager extends DefaultPluginManager {

  /**
   * Constructs a new RecurringUsageGroupManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Commerce/RecurringUsageGroup',
      $namespaces,
      $module_handler,
      'Drupal\commerce_recurring\Plugin\Commerce\RecurringUsageGroup\RecurringUsageGroupInterface',
      'Drupal\commerce_recurring\Annotation\CommerceRecurringUsageGroup'
    );

    $this->alterInfo('commerce_recurring_usage_group_info');
    $this->setCacheBackend($cache_backend, 'commerce_recurring_usage_group_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The recurring usage group %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}



