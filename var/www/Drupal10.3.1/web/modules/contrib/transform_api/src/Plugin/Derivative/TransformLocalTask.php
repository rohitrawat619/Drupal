<?php

namespace Drupal\transform_api\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class TransformLocalTask extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Creates a FieldUiLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, EntityTransformRepositoryInterface $entity_display_repository) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('transform_api.entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {
        // 'Manage transform' tab.
        $this->derivatives["transform_display_overview_$entity_type_id"] = [
          'route_name' => "entity.entity_transform_display.$entity_type_id.default",
          'weight' => 4,
          'title' => $this->t('Manage transform'),
          'base_route' => "entity.$entity_type_id.field_ui_fields",
        ];

        // Transform modes secondary tabs.
        // The same base $path for the menu item (with a placeholder) can be
        // used for all bundles of a given entity type; but depending on
        // administrator settings, each bundle has a different set of transform
        // modes available for customization. So we define menu items for all
        // transform modes, and use a route requirement to determine which ones
        // are actually visible for a given bundle.
        $this->derivatives['field_transform_display_default_' . $entity_type_id] = [
          'title' => 'Default',
          'route_name' => "entity.entity_transform_display.$entity_type_id.default",
          'parent_id' => "transform_api.fields:transform_display_overview_$entity_type_id",
          'weight' => -1,
        ];

        // One local task for each transform mode.
        $weight = 0;
        foreach ($this->entityDisplayRepository->getTransformModes($entity_type_id) as $transform_mode => $transform_mode_info) {
          $this->derivatives['field_transform_display_' . $transform_mode . '_' . $entity_type_id] = [
            'title' => $transform_mode_info['label'],
            'route_name' => "entity.entity_transform_display.$entity_type_id.transform_mode",
            'route_parameters' => [
              'transform_mode_name' => $transform_mode,
            ],
            'parent_id' => "transform_api.fields:transform_display_overview_$entity_type_id",
            'weight' => $weight++,
            'cache_tags' => $this->entityTypeManager->getDefinition('entity_transform_display')->getListCacheTags(),
          ];
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Alters the base_route definition for field_ui local tasks.
   *
   * @param array $local_tasks
   *   An array of local tasks plugin definitions, keyed by plugin ID.
   */
  public function alterLocalTasks(&$local_tasks) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        $local_tasks["transform_api.fields:transform_display_overview_$entity_type_id"]['base_route'] = $route_name;
        $local_tasks["transform_api.fields:field_transform_display_default_$entity_type_id"]['base_route'] = $route_name;

        foreach ($this->entityDisplayRepository->getTransformModes($entity_type_id) as $transform_mode => $transform_mode_info) {
          $local_tasks['transform_api.fields:field_transform_display_' . $transform_mode . '_' . $entity_type_id]['base_route'] = $route_name;
        }
      }
    }
  }

}
