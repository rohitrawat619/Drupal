<?php

namespace Drupal\transform_api\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Transform API routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = [
            'type' => 'entity:' . $bundle_entity_type,
          ];
        }
        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;

        $defaults = [
          'entity_type_id' => $entity_type_id,
        ];
        // If the entity type has no bundles, and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (!str_contains($path, '{bundle}')) {
          $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
        }

        $route = new Route(
          "$path/transform",
          [
            '_entity_form' => 'entity_transform_display.edit',
            '_title' => 'Manage transform',
            'transform_mode_name' => 'default',
          ] + $defaults,
          ['_transform_api_transform_mode_access' => 'administer ' . $entity_type_id . ' transform'],
          $options
        );
        $collection->add("entity.entity_transform_display.$entity_type_id.default", $route);

        $route = new Route(
          "$path/transform/{transform_mode_name}",
          [
            '_entity_form' => 'entity_transform_display.edit',
            '_title' => 'Manage transform',
          ] + $defaults,
          ['_transform_api_transform_mode_access' => 'administer ' . $entity_type_id . ' transform'],
          $options
        );
        $collection->add("entity.entity_transform_display.$entity_type_id.transform_mode", $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

}
