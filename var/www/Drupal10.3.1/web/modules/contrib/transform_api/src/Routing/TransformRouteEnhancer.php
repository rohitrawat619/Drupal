<?php

namespace Drupal\transform_api\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route Enhancer to hijack routes for outputting JSON instead of HTML.
 */
class TransformRouteEnhancer implements EnhancerInterface {

  /**
   * {@inheritDoc}
   */
  public function enhance(array $defaults, Request $request) {
    if ($request->query->has('format') && strtoupper($request->query->get('format')) === 'JSON') {
      $defaults['_controller'] = '\Drupal\transform_api\Controller\RequestPathController::enhance';
      $defaults['url'] = $request->getPathInfo();
      $request->query->remove('format');
      $request->setRequestFormat('json');
      if ($request->query->has('region')) {
        $defaults['_controller'] .= 'Region';
        $defaults['region'] = $request->query->get('region');
        $request->query->remove('region');
      }
    }
    return $defaults;
  }

}
