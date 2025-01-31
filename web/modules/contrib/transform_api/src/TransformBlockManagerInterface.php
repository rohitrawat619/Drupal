<?php

namespace Drupal\transform_api;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerInterface;

/**
 * Interface for the discovery and instantiation of transform block plugins.
 */
interface TransformBlockManagerInterface extends ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface, FilteredPluginManagerInterface {

}
