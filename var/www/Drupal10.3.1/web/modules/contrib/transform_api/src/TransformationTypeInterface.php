<?php

namespace Drupal\transform_api;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\transform_api\Transform\TransformInterface;

/**
 * Interface for transformation type plugins.
 */
interface TransformationTypeInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Transform a transform of this type into JSON.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $transform
   *   The transform to transform.
   *
   * @return array
   *   Transformed JSON array.
   *
   * @throws \Drupal\transform_api\Exception\ResponseTransformationException
   */
  public function transform(TransformInterface $transform);

}
