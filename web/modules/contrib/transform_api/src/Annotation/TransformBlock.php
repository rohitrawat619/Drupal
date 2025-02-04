<?php

namespace Drupal\transform_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a transform block annotation object.
 *
 * Plugin Namespace: Plugin\Transform\Block.
 *
 * @ingroup transform_api
 *
 * @see plugin_api
 *
 * @Annotation
 */
class TransformBlock extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the block.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

  /**
   * The category in the admin UI where the block will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context_definitions = [];

}
