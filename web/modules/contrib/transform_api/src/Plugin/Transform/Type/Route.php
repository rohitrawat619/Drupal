<?php

namespace Drupal\transform_api\Plugin\Transform\Type;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\transform_api\RouteTransformManager;
use Drupal\transform_api\Transform\TransformInterface;
use Drupal\transform_api\TransformationTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for route transform types.
 *
 * @TransformationType(
 *  id = "route",
 *  title = "Route"
 * )
 */
class Route extends TransformationTypeBase {

  /**
   * The route transform manager.
   *
   * @var \Drupal\transform_api\RouteTransformManager
   */
  protected RouteTransformManager $routeTransformManager;

  /**
   * Construct a route transform type plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\transform_api\RouteTransformManager $routeTransformManager
   *   The route transform manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteTransformManager $routeTransformManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeTransformManager = $routeTransformManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.transform_api.route_transform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform(TransformInterface $transform) {
    try {
      $route = $this->routeTransformManager->createInstance($transform->getValue('route_name'));
    }
    catch (PluginException) {
      return [];
    }
    if (empty($route)) {
      return [];
    }

    $transformation = [
      'type' => 'route',
    ] + $transform->getValues();
    $transformation += $route->transform($transform);

    return $transformation;
  }

}
