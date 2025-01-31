<?php

namespace Drupal\transform_api;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for route transform plugins.
 */
abstract class RouteTransformBase extends PluginSettingsBase implements RouteTransformInterface, ContainerFactoryPluginInterface, CacheableDependencyInterface {

  use StringTranslationTrait;
  use ContextAwarePluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
