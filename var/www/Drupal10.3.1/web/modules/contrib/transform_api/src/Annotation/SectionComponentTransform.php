<?php

namespace Drupal\transform_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a section component transform annotation object.
 *
 * Plugin Namespace: Plugin\Transform\SectionComponent.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class SectionComponentTransform extends Plugin {

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
