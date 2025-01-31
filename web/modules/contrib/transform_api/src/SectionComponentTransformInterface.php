<?php

namespace Drupal\transform_api;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;

/**
 * Interface for section component transform plugins.
 */
interface SectionComponentTransformInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Transform a SectionComponent of this type into JSON.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The SectionComponent to transform.
   * @param string $transform_mode
   *   The transform mode to use for transformation.
   *
   * @return array
   *   Transformed JSON array.
   */
  public function transform(SectionComponent $component, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE);

}
