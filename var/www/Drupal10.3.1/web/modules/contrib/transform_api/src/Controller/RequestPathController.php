<?php

namespace Drupal\transform_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\transform_api\Exception\ResponseTransformationException;
use Drupal\transform_api\Transform\RequestPathTransform;
use Drupal\transform_api\Transformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for handling request path transforms.
 */
class RequestPathController extends ControllerBase {

  /**
   * The transformer service.
   *
   * @var \Drupal\transform_api\Transformer
   */
  protected Transformer $transformer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Construct a RouteController object.
   *
   * @param \Drupal\transform_api\Transformer $transformer
   *   The transformer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(Transformer $transformer, RequestStack $requestStack) {
    $this->transformer = $transformer;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transform_api.transformer'),
      $container->get('request_stack')
    );
  }

  /**
   * Take an url and transform it's content into JSON.
   *
   * @param string $url
   *   The url to transform.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response with JSON.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function enhance($url) {
    try {
      $transform = new RequestPathTransform($url);
      return new JsonResponse($this->transformer->transformRoot($transform));
    }
    catch (ResponseTransformationException $exception) {
      return $exception->getResponse();
    }
  }

  /**
   * Take an url and transform it's content into JSON.
   *
   * @param string $url
   *   The url to transform.
   * @param string $region
   *   The region to transform.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response with JSON.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function enhanceRegion($url, $region) {
    try {
      $transform = new RequestPathTransform($url, $region);
      return new JsonResponse($this->transformer->transformRoot($transform));
    }
    catch (ResponseTransformationException $exception) {
      return $exception->getResponse();
    }
  }

}
