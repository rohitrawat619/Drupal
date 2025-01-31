<?php

namespace Drupal\transform_api\Transform;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\transform_api\Entity\EntityTransformDisplay;
use Drupal\transform_api\FieldTransformInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;

/**
 * A transform for a field.
 */
class FieldTransform extends TransformBase {

  /**
   * The field transform plugin.
   *
   * @var \Drupal\transform_api\FieldTransformInterface
   */
  protected $fieldTransform;

  /**
   * The entity where the field is stored.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Construct a FieldTransform.
   */
  public function __construct(FieldTransformInterface $fieldTransform, FieldableEntityInterface $entity, $field_name) {
    $this->entity = $entity;
    $this->fieldTransform = $fieldTransform;
    $this->values = [
      'entity_type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'field' => $field_name,
      'langcode' => $entity->language()->getId(),
      'transform_mode' => $fieldTransform->getTransformMode(),
    ];
    $this->addCacheableDependency($entity);
    $this->addCacheableDependency(EntityTransformDisplay::collectTransformDisplay($entity, $fieldTransform->getTransformMode()));
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'field';
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    return $this->fieldTransform->transform($this->entity->get($this->values['field']), $this->values['langcode']);
  }

  /**
   * Create a field transform from a field on an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity where the field is stored.
   * @param string $field_name
   *   The name of the field.
   * @param string $transform_mode
   *   The transform mode to use for transformation.
   *
   * @return FieldTransform
   *   A field transform.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function createFromEntity(FieldableEntityInterface $entity, $field_name, $transform_mode) {
    /** @var \Drupal\transform_api\FieldTransformManager $fieldTransformManager */
    $fieldTransformManager = \Drupal::service('plugin.manager.transform_api.field_transform');
    $display = EntityTransformDisplay::collectTransformDisplay($entity, $transform_mode);
    $definition = $display->getComponent($field_name);
    $configuration = [
      'field_definition' => $entity->get($field_name)->getFieldDefinition(),
      'settings' => $definition['settings'] ?? [],
      'label' => $definition['label'] ?? '',
      'transform_mode' => $transform_mode ?? EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE,
      'third_party_settings' => $definition['third_party_settings'] ?? [],
    ];
    $plugin = $fieldTransformManager->createInstance($definition['type'], $configuration);
    $plugin->prepareTransform([$entity->id() => $entity->get($field_name)]);
    return new self($plugin, $entity, $field_name);
  }

}
