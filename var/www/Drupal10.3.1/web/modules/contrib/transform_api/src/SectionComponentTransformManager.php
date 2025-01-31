<?php

namespace Drupal\transform_api;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The section component transform manager.
 */
class SectionComponentTransformManager extends DefaultPluginManager {

  /**
   * Constructs a SectionComponentTransformManager object.
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
      'Plugin/Transform/SectionComponent',
      $namespaces,
      $module_handler,
      'Drupal\transform_api\SectionComponentTransformInterface',
      'Drupal\transform_api\Annotation\SectionComponentTransform'
    );
    $this->alterInfo('transform_api_section_component_info');
    $this->setCacheBackend($cache_backend, 'transform_api_section_component_plugins');
  }

  /**
   * Create an instance of a section component transform plugin.
   *
   * @param string $plugin_id
   *   Plugin ID to create instance of.
   * @param array $configuration
   *   Configuration of the plugin.
   *
   * @return SectionComponentTransformInterface
   *   The section component transform plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\transform_api\SectionComponentTransformInterface $transform */
    $transform = parent::createInstance($plugin_id, $configuration);
    return $transform;
  }

}
