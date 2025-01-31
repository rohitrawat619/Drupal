<?php

namespace Drupal\transform_api\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityDisplayPluginCollection;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\transform_api\Configs\EntityTransformDisplayInterface;

/**
 * Defines an entity transform configuration.
 *
 * @ConfigEntityType(
 *   id = "entity_transform_display",
 *   label = @Translation("Entity transform display"),
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   handlers = {
 *     "access" = "\Drupal\Core\Entity\Entity\Access\EntityViewDisplayAccessControlHandler",
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "mode",
 *     "content",
 *     "hidden",
 *   }
 * )
 */
class EntityTransformDisplay extends EntityDisplayBase implements EntityTransformDisplayInterface {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'transform';

  /**
   * The transformer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $transformer;

  /**
   * Returns the display objects used to transform a set of entities.
   *
   * Depending on the configuration of the transform mode for each bundle,
   * this can be either the display object associated with the transform mode,
   * or the 'default' display.
   *
   * This method should only be used internally when transforming an entity.
   * When assigning suggested display options for a component in a given
   * transform mode, EntityDisplayRepositoryInterface::getTransformDisplay()
   * should be used instead, in order to avoid inadvertently modifying the
   * output of other transform modes that might happen to use the 'default'
   * display too. Those options will then be effectively applied only if
   * the transform mode is configured to use them.
   *
   * hook_entity_transform_display_alter() is invoked on each display, allowing
   * 3rd party code to alter the display options held in the display before they
   * are used to generate transform arrays.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   The entities being transformed. They should be of the same entity type.
   * @param string $transform_mode
   *   The transform mode being transformed.
   *
   * @return \Drupal\transform_api\Configs\EntityTransformDisplayInterface[]
   *   The display objects to use to transform the entities, keyed by entity
   *   bundle.
   *
   * @see \Drupal\transform_api\Repository\EntityTransformRepositoryInterface::getTransformDisplay()
   * @see hook_entity_transform_display_alter()
   */
  public static function collectTransformDisplays(array $entities, string $transform_mode): array {
    if (empty($entities)) {
      return [];
    }

    // Collect entity type and bundles.
    $entity_type = current($entities)->getEntityTypeId();
    $bundles = [];
    foreach ($entities as $entity) {
      $bundles[$entity->bundle()] = TRUE;
    }
    $bundles = array_keys($bundles);

    // For each bundle, check the existence and status of:
    // - the display for the view mode,
    // - the 'default' display.
    $candidate_ids = [];
    foreach ($bundles as $bundle) {
      if ($transform_mode != 'default') {
        $candidate_ids[$bundle][] = $entity_type . '.' . $bundle . '.' . $transform_mode;
      }
      $candidate_ids[$bundle][] = $entity_type . '.' . $bundle . '.default';
    }
    $results = \Drupal::entityQuery('entity_transform_display')
      ->condition('id', NestedArray::mergeDeepArray($candidate_ids))
      ->condition('status', TRUE)
      ->execute();

    // For each bundle, select the first valid candidate display, if any.
    $load_ids = [];
    foreach ($bundles as $bundle) {
      foreach ($candidate_ids[$bundle] as $candidate_id) {
        if (isset($results[$candidate_id])) {
          $load_ids[$bundle] = $candidate_id;
          break;
        }
      }
    }

    // Load the selected displays.
    $storage = \Drupal::entityTypeManager()->getStorage('entity_transform_display');
    $displays = $storage->loadMultiple($load_ids);

    $displays_by_bundle = [];
    foreach ($bundles as $bundle) {
      // Use the selected display if any, or create a fresh runtime object.
      if (isset($load_ids[$bundle])) {
        $display = $displays[$load_ids[$bundle]];
      }
      else {
        $display = $storage->create([
          'targetEntityType' => $entity_type,
          'bundle' => $bundle,
          'mode' => $transform_mode,
          'status' => TRUE,
        ]);
      }

      // Let the display know which view mode was originally requested.
      $display->originalMode = $transform_mode;

      // Let modules alter the display.
      $display_context = [
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'transform_mode' => $transform_mode,
      ];
      \Drupal::moduleHandler()->alter('entity_transform_display', $display, $display_context);

      $displays_by_bundle[$bundle] = $display;
    }

    return $displays_by_bundle;
  }

  /**
   * Returns the display object used to transform an entity.
   *
   * See the collectTransformDisplays() method for details.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being transformed.
   * @param string $transform_mode
   *   The transform mode.
   *
   * @return \Drupal\transform_api\Configs\EntityTransformDisplayInterface
   *   The display object that should be used to transform the entity.
   *
   * @see \Drupal\transform_api\Entity\EntityTransformDisplay::collectTransformDisplays()
   */
  public static function collectTransformDisplay(FieldableEntityInterface $entity, $transform_mode) {
    $displays = static::collectTransformDisplays([$entity], $transform_mode);
    return $displays[$entity->bundle()];
  }

  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.transform_api.field_transform');
    $this->transformer = \Drupal::service('transform_api.transformer');

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the formatter object from the stored display properties.
    if (($configuration = $this->getComponent($field_name)) && isset($configuration['type']) && ($definition = $this->getFieldDefinition($field_name))) {
      $formatter = $this->pluginManager->getInstance([
        'field_definition' => $definition,
        'transform_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration,
      ]);
    }
    else {
      $formatter = NULL;
    }

    // Persist the formatter object.
    $this->plugins[$field_name] = $formatter;
    return $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    $configurations = [];
    foreach ($this->getComponents() as $field_name => $configuration) {
      if (!empty($configuration['type']) && ($field_definition = $this->getFieldDefinition($field_name))) {
        $configurations[$configuration['type']] = $configuration + [
          'field_definition' => $field_definition,
          'transform_mode' => $this->originalMode,
        ];
      }
    }

    return [
      'transforms' => new EntityDisplayPluginCollection($this->pluginManager, $configurations),
    ];
  }

}
