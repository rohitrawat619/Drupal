<?php

namespace Drupal\transform_api\Plugin\Transform\Type;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\transform_api\Configs\EntityTransformDisplayInterface;
use Drupal\transform_api\Entity\EntityTransformDisplay;
use Drupal\transform_api\EventSubscriber\TransformationCache;
use Drupal\transform_api\FieldTransformManager;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transform\FieldTransform;
use Drupal\transform_api\Transform\SimpleTransform;
use Drupal\transform_api\Transform\TransformInterface;
use Drupal\transform_api\TransformationTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for entity transform types.
 *
 * @TransformationType(
 *  id = "entity",
 *  title = "Entity transform"
 * )
 */
class Entity extends TransformationTypeBase {

  /**
   * The transformation caching service.
   *
   * @var \Drupal\transform_api\EventSubscriber\TransformationCache
   */
  protected TransformationCache $transformationCache;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The field transform manager.
   *
   * @var \Drupal\transform_api\FieldTransformManager
   */
  protected FieldTransformManager $fieldTransformManager;

  /**
   * The repository for transform modes.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected EntityTransformRepositoryInterface $entityTransformRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * Construct an entity transform type plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\transform_api\EventSubscriber\TransformationCache $transformationCache
   *   The transformation caching service.
   * @param \Drupal\transform_api\FieldTransformManager $fieldTransformManager
   *   The field transform manager.
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entityTransformRepository
   *   The repository for transform modes.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, TransformationCache $transformationCache, FieldTransformManager $fieldTransformManager, EntityTransformRepositoryInterface $entityTransformRepository, ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->transformationCache = $transformationCache;
    $this->fieldTransformManager = $fieldTransformManager;
    $this->entityTransformRepository = $entityTransformRepository;
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('transform_api.transformation_cache'),
      $container->get('plugin.manager.transform_api.field_transform'),
      $container->get('transform_api.entity_display.repository'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform(TransformInterface $transform) {
    $langcode = $transform->getValue('langcode') ?? $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    /** @var \Drupal\transform_api\Transform\EntityTransform $entity_transform */
    $entity_transform = $transform;
    if (is_array($transform->getValue('ids'))) {
      return $this->prepareMultipleTransforms($transform->getValue('entity_type'), $transform->getValue('ids'), $transform->getValue('transform_mode') ?? EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE, $langcode);
    }
    elseif (!is_null($entity_transform->getEntity())) {
      return $this->transformEntity($entity_transform->getEntity(), $entity_transform->getFields(), $entity_transform->getDisplay());
    }
    else {
      return $this->loadAndTransformEntity($transform->getValue('entity_type'), $transform->getValue('ids'), $transform->getValue('transform_mode') ?? EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE, $langcode);
    }
  }

  /**
   * Load and then transform an entity.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param int|string $id
   *   The id of the entity.
   * @param string $transform_mode
   *   Transform to use for transformation.
   * @param string $langcode
   *   The language code of the language to transform.
   *
   * @return array
   *   The JSON array of the entity.
   */
  protected function loadAndTransformEntity($entity_type_id, $id, $transform_mode, $langcode) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $storage->load($id);
    if ($entity instanceof TranslatableInterface) {
      if ($entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
    }
    if (!empty($entity)) {
      return $this->prepareAndTransformEntity($entity, $transform_mode);
    }
    else {
      return [];
    }
  }

  /**
   * Take multiple ids and fetch the cache or load the entities to transform.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param array $ids
   *   The ids of the entities.
   * @param string $transform_mode
   *   Transform to use for transformations.
   * @param string $langcode
   *   The language code of the language to transform.
   *
   * @return array
   *   The JSON array of the entities.
   */
  protected function prepareMultipleTransforms($entity_type_id, array $ids, $transform_mode, $langcode) {
    $transformation = [];
    $cached = [];
    $missing_ids = [];
    foreach ($ids as $id) {
      $transform = new EntityTransform($entity_type_id, $id, $transform_mode, $langcode);
      $entity_transformation = $this->transformationCache->get($transform);
      if ($entity_transformation === FALSE) {
        // We have no cache of this entity, we must load it.
        $missing_ids[] = $id;
      }
      else {
        // We already cached this entity, no need to load it.
        $cached[$id] = $entity_transformation;
      }
    }

    $fields = [];
    $displays = [];
    if (!empty($missing_ids)) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $entities */
      $entities = $storage->loadMultiple($missing_ids);
      if (!empty($entities)) {
        $displays = EntityTransformDisplay::collectTransformDisplays($entities, $transform_mode);
        $field_values = [];
        foreach ($missing_ids as $id) {
          if (empty($entities[$id])) {
            continue;
          }
          $entity = $entities[$id];
          if ($entity instanceof TranslatableInterface) {
            if ($entity->hasTranslation($langcode)) {
              $entity = $entities[$id] = $entity->getTranslation($langcode);
            }
          }

          $bundle = $entity->bundle();
          if (empty($fields[$bundle])) {
            $fields[$bundle] = [];
            $field_values[$bundle] = [];
            foreach ($displays[$bundle]->getComponents() as $field_name => $definition) {
              $configuration = [
                'field_definition' => $entity->get($field_name)
                  ->getFieldDefinition(),
                'settings' => $definition['settings'] ?? [],
                'label' => $definition['label'] ?? '',
                'transform_mode' => $transform_mode,
                'third_party_settings' => $definition['third_party_settings'] ?? [],
              ];
              $fields[$bundle][$field_name] = $this->fieldTransformManager->createInstance($definition['type'], $configuration);
              $field_values[$bundle][$field_name] = [];
            }
          }
          foreach ($fields[$bundle] as $field_name => $plugin) {
            $field_values[$bundle][$field_name][$id] = $entity->get($field_name);
          }
        }
        foreach ($fields as $bundle => $plugins) {
          foreach ($plugins as $field_name => $plugin) {
            $plugin->prepareTransform($field_values[$bundle][$field_name]);
          }
        }
      }
    }

    foreach ($ids as $id) {
      if (in_array($id, $missing_ids)) {
        if (empty($entities[$id])) {
          continue;
        }
        $transformation[] = EntityTransform::createPrepared($entities[$id], $fields[$entities[$id]->bundle()], $displays[$entities[$id]->bundle()]);
      }
      else {
        $transformation[] = new SimpleTransform($cached[$id]);
      }
    }

    return $transformation;
  }

  /**
   * Take an entity object and find the fields and transform it.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object that needs to be transformed.
   * @param string $transform_mode
   *   (Optional) Transform to use for transformations.
   *
   * @return array
   *   The transform array.
   */
  public function prepareAndTransformEntity(FieldableEntityInterface $entity, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    $plugins = [];
    $display = EntityTransformDisplay::collectTransformDisplay($entity, $transform_mode);
    foreach ($display->getComponents() as $field_name => $definition) {
      $configuration = [
        'field_definition' => $entity->get($field_name)->getFieldDefinition(),
        'settings' => $definition['settings'] ?? [],
        'label' => $definition['label'] ?? '',
        'transform_mode' => $transform_mode ?? EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE,
        'third_party_settings' => $definition['third_party_settings'] ?? [],
      ];
      $plugins[$field_name] = $this->fieldTransformManager->createInstance($definition['type'], $configuration);
      $plugins[$field_name]->prepareTransform([$entity->id() => $entity->get($field_name)]);
    }
    return $this->transformEntity($entity, $plugins, $display);
  }

  /**
   * Take a prepared entity with fields and transform it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object that needs to be transformed.
   * @param array $plugins
   *   An array of field plugins.
   * @param \Drupal\transform_api\Configs\EntityTransformDisplayInterface|null $display
   *   The transform mode if available.
   *
   * @return array
   *   The transform array.
   */
  public function transformEntity(EntityInterface $entity, array $plugins = [], EntityTransformDisplayInterface $display = NULL) {
    $cacheMetadata = CacheableMetadata::createFromObject($entity);
    $transformation = [];
    $transformation['type'] = 'entity';
    $transformation['entity_type'] = $entity->getEntityTypeId();
    $transformation['bundle'] = $entity->bundle();
    $transformation['id'] = $entity->id();
    $transformation['label'] = $entity->label();
    $transformation['transform_mode'] = $display->getMode();
    $transformation['#entity'] = $entity;
    if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
      $cacheMetadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
    }
    if ($entity instanceof FieldableEntityInterface && !empty($display)) {
      $cacheMetadata->addCacheableDependency($display);
      foreach ($display->getComponents() as $field_name => $definition) {
        $transformation[$definition['name'] ?? $field_name] = new FieldTransform($plugins[$field_name], $entity, $field_name);
      }
    }
    $cacheMetadata->applyTo($transformation);
    return $transformation;
  }

}
