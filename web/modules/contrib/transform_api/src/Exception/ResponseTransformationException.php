<?php

namespace Drupal\transform_api\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * A transformation exception with a response.
 */
class ResponseTransformationException extends TransformationException {

  /**
   * The http response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected Response $response;

  /**
   * Construct a ResponseTransformationException object.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   Response to return.
   * @param string $message
   *   The exception message.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(Response $response, string $message = "", ?\Throwable $previous = NULL) {
    $this->response = $response;
    parent::__construct($message, $response->getStatusCode(), $previous);
  }

  /**
   * Return the http response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The http response.
   */
  public function getResponse(): Response {
    return $this->response;
  }

}
