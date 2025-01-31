<?php

namespace Drupal\transform_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an engine annotation object.
 *
 * Plugin Namespace: Plugin\Transform\Type.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class TransformationType extends Plugin {

  /**
   * The transform plugin ID.
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
