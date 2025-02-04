<?php

namespace Drupal\transform_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The transformation type manager.
 */
class TransformationTypeManager extends DefaultPluginManager {

  /**
   * Constructs a TransformationTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/Transform/Type',
      $namespaces,
      $module_handler,
      'Drupal\transform_api\TransformationTypeInterface',
      'Drupal\transform_api\Annotation\TransformationType'
    );
    $this->alterInfo('transform_api_type_info');
    $this->setCacheBackend($cache_backend, 'transform_api_type_plugins');
  }

  /**
   * Create an instance of a transformation type plugin.
   *
   * @param string $plugin_id
   *   Plugin ID to create instance of.
   * @param array $configuration
   *   Configuration of the plugin.
   *
   * @return TransformationTypeInterface
   *   The transformation type plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\transform_api\TransformationTypeInterface $transform */
    $transform = parent::createInstance($plugin_id, $configuration);
    return $transform;
  }

}
