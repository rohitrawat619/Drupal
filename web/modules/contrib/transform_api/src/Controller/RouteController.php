<?php

namespace Drupal\transform_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\transform_api\Transform\RouteTransform;
use Drupal\transform_api\Transformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for handling route transforms.
 */
class RouteController extends ControllerBase {

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
   * Handles transforming routes into JSON.
   */
  public function route() {
    $route_name = $this->requestStack->getCurrentRequest()->get('route_name');
    $transform = new RouteTransform($route_name);
    return new JsonResponse($this->transformer->transformRoot($transform));
  }

}
