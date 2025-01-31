<?php

namespace Drupal\transform_api\Plugin\Transform\Block;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\transform_api\TransformBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a breadcrumbs block.
 *
 * @TransformBlock(
 *  id = "breadcrumbs",
 *  admin_label = "Breadcrumbs",
 *  category = "Breadcrumbs",
 * )
 */
class BreadcrumbsTransformBlock extends TransformBlockBase {

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   * Constructs a new SystemBreadcrumbBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumb_manager
   *   The breadcrumb manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BreadcrumbBuilderInterface $breadcrumb_manager, RouteMatchInterface $routeMatch, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->logger = $logger->get('premium_core');
    $this->breadcrumbManager = $breadcrumb_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $routeMatch */
    $routeMatch = $container->get('current_route_match');
    /** @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumb */
    $breadcrumb = $container->get('breadcrumb');
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger */
    $logger = $container->get('logger.factory');
    return new static($configuration, $plugin_id, $plugin_definition, $breadcrumb, $routeMatch, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    $transform = [
      'type' => 'breadcrumbs',
      'links' => [],
    ];
    $build = $this->breadcrumbManager->build($this->routeMatch);
    $cacheMetadata = CacheableMetadata::createFromRenderArray($build->toRenderable());
    $links = $build->getLinks();
    if (!empty($links)) {
      foreach ($links as $item) {
        $transform['links'][] = [
          'url' => $item->getUrl()->toString(),
          'title' => $item->getText(),
        ];
      }
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $route_entity */
    $route_entity = $this->getEntityFromRouteMatch($this->routeMatch);
    if ($route_entity instanceof ContentEntityInterface && $route_entity->hasField('field_hide_breadcrumb')) {
      try {
        $data = $route_entity->get('field_hide_breadcrumb')->first();
        if (!is_null($data) && (int) $data->getValue()['value'] === 1) {
          $cacheMetadata->setCacheContexts(['route'])->setCacheTags([]);
          $transform = [];
        }
      }
      catch (MissingDataException $e) {
        $this->logger->error($e->getMessage());
      }
      $cacheMetadata->addCacheableDependency($route_entity);
    }

    $status = \Drupal::requestStack()->getCurrentRequest()->attributes->get('exception');
    if ($status && $status->getStatusCode() != 200) {
      $cacheMetadata->setCacheMaxAge(0);
    }

    $cacheMetadata->applyTo($transform);

    return $transform;
  }

  /**
   * Returns an entity parameter from a route match object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return mixed|null
   *   The entity, or null if it's not an entity route.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$route) {
      return NULL;
    }

    $entity_type_id = $this->getEntityTypeFromRoute($route);
    if ($entity_type_id) {
      return $route_match->getParameter($entity_type_id);
    }

    return NULL;
  }

  /**
   * Return the entity type id from a route object.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   *
   * @return string|null
   *   The entity type id, null if it doesn't exist.
   */
  protected function getEntityTypeFromRoute(Route $route): ?string {
    if (!empty($route->getOptions()['parameters'])) {
      foreach ($route->getOptions()['parameters'] as $option) {
        if (isset($option['type']) && str_starts_with($option['type'], 'entity:')) {
          return substr($option['type'], strlen('entity:'));
        }
      }
    }

    return NULL;
  }

}
