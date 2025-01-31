<?php

namespace Drupal\transform_api\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling entity transforms.
 */
class EntityController extends ControllerBase {
  /**
   * The transformer service.
   *
   * @var \Drupal\transform_api\Transformer
   */
  protected $transformer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Construct a EntityController object.
   *
   * @param \Drupal\transform_api\Transformer $transformer
   *   The transformer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(Transformer $transformer, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->transformer = $transformer;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transform_api.transformer'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Transform an entity into JSON.
   *
   * @param string $entity_type
   *   The entity type of the entity to transform.
   * @param string $id
   *   The id of the entity to transform.
   * @param string $transform_mode
   *   (Optional) The transform mode to use for the transformation.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response with JSON.
   */
  public function view($entity_type, $id, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE): JsonResponse {
    $transform = new EntityTransform($entity_type, $id, $transform_mode);
    return new JsonResponse($this->transformer->transformRoot($transform));
  }

}
