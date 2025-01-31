<?php

namespace Drupal\transform_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a field transform annotation object.
 *
 * Plugin Namespace: Plugin\Transform\Field.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FieldTransform extends Plugin {

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
  public $label;

  /**
   * The field types supported by the plugin.
   *
   * @var array
   */
  public $field_types;

}
