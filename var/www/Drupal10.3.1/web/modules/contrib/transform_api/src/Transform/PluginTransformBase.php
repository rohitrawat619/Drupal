<?php

namespace Drupal\transform_api\Transform;

/**
 * Base class for transforms with a transform type plugin.
 */
abstract class PluginTransformBase extends TransformBase {

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function transform() {
    /** @var \Drupal\transform_api\TransformationTypeManager $transformationTypeManager */
    $transformationTypeManager = \Drupal::service('plugin.manager.transform_api.transformation_type');
    $plugin = $transformationTypeManager->createInstance($this->getTransformType(), $this->getValues());
    return $plugin->transform($this);
  }

}
