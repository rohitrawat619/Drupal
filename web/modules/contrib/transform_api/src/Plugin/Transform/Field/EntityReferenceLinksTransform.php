<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\transform_api\FieldTransformBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform field plugin for entity reference field types as links.
 *
 * @FieldTransform(
 *  id = "entity_reference_links",
 *  label = @Translation("Entity reference links"),
 *  field_types = {
 *    "entity_reference"
 *  }
 * )
 */
class EntityReferenceLinksTransform extends FieldTransformBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityReferenceLinksTransform object.
   *
   * @param string $plugin_id
   *   The plugin_id for the transform.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the transform is associated.
   * @param array $settings
   *   The transform settings.
   * @param string $label
   *   The transform label display setting.
   * @param string $transform_mode
   *   The transform mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $transform_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['transform_mode'], $configuration['third_party_settings'], $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $metadata = new CacheableMetadata();
    $entity_type_id = $items->getSetting('target_type');
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      $ids = [];
      if (!empty($item->getValue()['target_id'])) {
        $ids[] = $item->getValue()['target_id'];
      }
      if (!empty($ids)) {
        $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids);
        foreach ($entities as $entity) {
          if ($entity instanceof TranslatableInterface) {
            if ($entity->hasTranslation($langcode)) {
              $entity = $entity->getTranslation($langcode);
            }
          }
          $elements[] = $this->transformEntity($entity);
          $metadata->addCacheableDependency($entity);
        }
      }
    }
    if (!empty($elements)) {
      $metadata->applyTo($elements);
    }
    return $elements;
  }

  /**
   * Take the referenced entity and transform some information on it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to transform information from.
   *
   * @return mixed
   *   Transformed information about the entity.
   */
  protected function transformEntity(EntityInterface $entity) {
    return [
      'id' => $entity->id(),
      'label' => $entity->label(),
      'url' => $entity->toUrl()->toString(),
    ];
  }

}
