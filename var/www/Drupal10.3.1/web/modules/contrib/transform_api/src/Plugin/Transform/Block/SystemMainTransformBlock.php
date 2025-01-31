<?php

namespace Drupal\transform_api\Plugin\Transform\Block;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transform\RouteTransform;
use Drupal\transform_api\TransformBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides a 'Main page content' block.
 *
 * @TransformBlock(
 *   id = "system_main_block",
 *   admin_label = @Translation("Main page content"),
 *   category = @Translation("System"),
 * )
 */
class SystemMainTransformBlock extends TransformBlockBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The router service.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected RouterInterface $router;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * Constructs a SystemMainTransformBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouterInterface $router, ConfigFactoryInterface $configFactory, RequestStack $requestStack, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->router = $router;
    $this->configFactory = $configFactory;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('router'),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    $url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $this->requestStack->getCurrentRequest()->getBaseUrl() . $this->requestStack->getCurrentRequest()->getPathInfo();
    $match = $this->router->match($url);
    $route_entity = $this->getEntityFromMatchArray($match);
    $cacheMetadata = new CacheableMetadata();
    $cacheMetadata->setCacheContexts(['route']);

    if ($route_entity instanceof ContentEntityInterface &&
      (str_starts_with($match['_route'], 'entity.')) &&
      (str_ends_with($match['_route'], '.canonical'))) {
      $cacheMetadata->addCacheableDependency($route_entity);
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      if ($route_entity->hasTranslation($langcode)) {
        $route_entity = $route_entity->getTranslation($langcode);
      }
      $transform = [
        '#collapse' => TRUE,
        'content' => EntityTransform::createFromEntity($route_entity, 'full'),
      ];
    }
    else {
      /** @var \Symfony\Component\HttpFoundation\ParameterBag $parameter_bag */
      $parameter_bag = $match['_raw_variables'];
      $transform = [
        '#collapse' => TRUE,
        'content' => new RouteTransform($match['_route'], $parameter_bag->all()),
      ];
    }
    $cacheMetadata->applyTo($transform);
    return $transform;
  }

  /**
   * Returns an entity parameter from a route match object.
   *
   * @return mixed|null
   *   The entity, or null if it's not an entity route.
   */
  protected function getEntityFromMatchArray(array $array) {
    $route = $array['_route_object'] ?? NULL;
    if (!$route) {
      return NULL;
    }

    $entity_type_id = $this->getEntityTypeFromRoute($route);
    if ($entity_type_id) {
      return $array[$entity_type_id];
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
