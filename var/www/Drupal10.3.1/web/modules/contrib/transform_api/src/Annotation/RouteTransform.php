<?php

namespace Drupal\transform_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a route transform annotation object.
 *
 * Plugin Namespace: Plugin\Transform\Route.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class RouteTransform extends Plugin {

  /**
   * The route name.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
