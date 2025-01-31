<?php

namespace Drupal\transform_api\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default implementation for menu link plugins.
 */
class ExtraLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The admin toolbar tools configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Construct an ExtraLinks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
    $this->themeHandler = $theme_handler;
    $this->config = $config_factory->get('admin_toolbar_tools.settings');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('router.route_provider'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!$this->moduleHandler->moduleExists('admin_toolbar_tools')) {
      return [];
    }

    $links = [];
    $admin_toolbar_parent = 'admin_toolbar_tools.extra_links';
    $max_bundle_number = $this->config->get('max_bundle_number');
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entities = [];
    foreach ($entity_types as $key => $entity_type) {
      if ($entity_type->getBundleEntityType() && ($entity_type->get('field_ui_base_route') != '')) {
        $content_entities[$key] = [
          'content_entity' => $key,
          'content_entity_bundle' => $entity_type->getBundleEntityType(),
        ];
      }
    }

    // Adds common links to entities.
    foreach ($content_entities as $entities) {
      $content_entity_bundle = $entities['content_entity_bundle'];
      $content_entity = $entities['content_entity'];
      $content_entity_bundle_storage = $this->entityTypeManager->getStorage($content_entity_bundle);
      $bundles_ids = $content_entity_bundle_storage->getQuery()->sort('weight')->pager($max_bundle_number)->accessCheck(TRUE)->execute();
      $bundles = $this->entityTypeManager->getStorage($content_entity_bundle)->loadMultiple($bundles_ids);
      foreach ($bundles as $machine_name => $bundle) {
        // Normally, the edit form for the bundle would be its root link.
        $content_entity_bundle_root = NULL;
        if ($this->routeExists('entity.' . $content_entity_bundle . '.overview_form')) {
          // Some bundles have an overview/list form that make a better root
          // link.
          $content_entity_bundle_root = 'entity.' . $content_entity_bundle . '.overview_form.' . $machine_name;
        }
        if ($this->routeExists('entity.' . $content_entity_bundle . '.edit_form')) {
          $key = 'entity.' . $content_entity_bundle . '.edit_form.' . $machine_name;
          if (empty($content_entity_bundle_root)) {
            $content_entity_bundle_root = $key;
          }
        }
        if ($this->moduleHandler->moduleExists('field_ui')) {
          if ($this->routeExists('entity.entity_transform_display.' . $content_entity . '.default')) {
            $links['entity.entity_transform_display.' . $content_entity . '.default.' . $machine_name] = [
              'title' => $this->t('Manage transform'),
              'route_name' => 'entity.entity_transform_display.' . $content_entity . '.default',
              'parent' => $admin_toolbar_parent . ':' . $content_entity_bundle_root,
              'route_parameters' => [$content_entity_bundle => $machine_name],
              'weight' => 3,
            ] + $base_plugin_definition;
          }
        }
      }
    }

    if ($this->moduleHandler->moduleExists('field_ui')) {
      $links['field_ui.entity_transform_mode_add'] = [
        'title' => $this->t('Add transform mode'),
        'route_name' => 'transform_api.entity_transform_mode_add',
        'parent' => 'entity.entity_transform_mode.collection',
      ] + $base_plugin_definition;
    }

    return $links;
  }

  /**
   * Determine if a route exists by name.
   *
   * @param string $route_name
   *   The name of the route to check.
   *
   * @return bool
   *   Whether a route with that route name exists.
   */
  public function routeExists($route_name) {
    return (count($this->routeProvider->getRoutesByNames([$route_name])) === 1);
  }

}
