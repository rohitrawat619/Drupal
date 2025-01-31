<?php

namespace Drupal\transform_api\Transform;

/**
 * A transform for routes.
 */
class RouteTransform extends PluginTransformBase {

  /**
   * Construct a RouteTransform.
   *
   * @param string $route_name
   *   Route name to transform.
   * @param array $parameters
   *   Route parameters for the route.
   */
  public function __construct($route_name, $parameters = []) {
    $this->values = [
      'route_name' => $route_name,
    ] + $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'route';
  }

}
