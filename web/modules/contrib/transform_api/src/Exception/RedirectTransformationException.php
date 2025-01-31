<?php

namespace Drupal\transform_api\Exception;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * An exception for when transform API encounters a redirect.
 */
class RedirectTransformationException extends ResponseTransformationException {

  /**
   * Construct a RedirectTransformationException object.
   *
   * @param string $route_name
   *   The route to redirect to.
   * @param array $route_parameters
   *   The route parameters to redirect with.
   * @param array $options
   *   The route options to redirect with.
   * @param int $status
   *   The redirect status code.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct($route_name, array $route_parameters = [], array $options = [], $status = 302, ?\Throwable $previous = NULL) {
    $options['absolute'] = TRUE;
    $options['query']['format'] = 'json';
    $url = Url::fromRoute($route_name, $route_parameters, $options)->toString(FALSE);
    $response = new RedirectResponse($url, $status);
    $message = 'Redirecting to ' . $url;
    parent::__construct($response, $message, $previous);
  }

}
