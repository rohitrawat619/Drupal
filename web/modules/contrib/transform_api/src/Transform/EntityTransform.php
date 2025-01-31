<?php

namespace Drupal\transform_api\Transform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\transform_api\Configs\EntityTransformDisplayInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;

/**
 * A transform of one or more entities.
 */
class EntityTransform extends PluginTransformBase {

  /**
   * The entity to transform.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected EntityInterface|null $entity = NULL;

  /**
   * The transform mode to use for transformation.
   *
   * @var \Drupal\transform_api\Configs\EntityTransformDisplayInterface|null
   */
  protected EntityTransformDisplayInterface|null $display = NULL;

  /**
   * Field transforms for the fields of the entity.
   *
   * @var \Drupal\transform_api\FieldTransformInterface[]
   */
  protected array $fields = [];

  /**
   * Construct a EntityTransform.
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param int|string|array $ids
   *   One or more ids of entities.
   * @param string $transform_mode
   *   (Optional) The transform mode to use for transformation.
   * @param string $langcode
   *   (Optional) The language to use for transformation.
   */
  public function __construct($entity_type, $ids, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE, $langcode = NULL) {
    $this->values = [
      'entity_type' => $entity_type,
      'ids' => $ids,
      'transform_mode' => $transform_mode,
      'langcode' => $langcode ?? \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
    ];
    if (is_array($ids)) {
      foreach ($ids as $id) {
        $this->cacheTags[] = $entity_type . ':' . $id;
      }
    }
    else {
      $this->cacheTags[] = $entity_type . ':' . $ids;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'entity';
  }

  /**
   * {@inheritdoc}
   */
  public function getAlterIdentifiers() {
    return [$this->getTransformType(), $this->values['entity_type']];
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return is_array($this->getValue('ids'));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldBeCached() {
    return !$this->isMultiple();
  }

  /**
   * Return the entity to be transformed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if not found.
   */
  public function getEntity(): ?EntityInterface {
    return $this->entity;
  }

  /**
   * Set the entity to be transformed.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to be transformed.
   */
  public function setEntity(?EntityInterface $entity): void {
    $this->entity = $entity;
  }

  /**
   * Return the field transforms for the fields on the entity.
   *
   * @return \Drupal\transform_api\FieldTransformInterface[]
   *   Array of field transforms.
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * Set the field transforms for the fields on the entity.
   *
   * @param \Drupal\transform_api\FieldTransformInterface[] $fields
   *   Array of field transforms.
   */
  public function setFields(array $fields): void {
    $this->fields = $fields;
  }

  /**
   * Return the transform mode used to be used for transformation.
   *
   * @return \Drupal\transform_api\Configs\EntityTransformDisplayInterface|null
   *   The transform mode.
   */
  public function getDisplay(): ?EntityTransformDisplayInterface {
    return $this->display;
  }

  /**
   * Set the transform mode to be used for transformation.
   *
   * @param \Drupal\transform_api\Configs\EntityTransformDisplayInterface|null $display
   *   The transform mode.
   */
  public function setDisplay(?EntityTransformDisplayInterface $display): void {
    $this->display = $display;
  }

  /**
   * Create an EntityTransform that is already prepared for transformation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be transformed.
   * @param \Drupal\transform_api\FieldTransformInterface[] $fields
   *   The field transforms for the entity fields.
   * @param \Drupal\transform_api\Configs\EntityTransformDisplayInterface $display
   *   The transform display mode to be used for transformation.
   *
   * @return EntityTransform
   *   A fully prepared EntityTransform.
   */
  public static function createPrepared(EntityInterface $entity, array $fields, EntityTransformDisplayInterface $display): EntityTransform {
    $transform = new self($entity->getEntityTypeId(), $entity->id(), $display->getMode());
    $transform->setEntity($entity);
    $transform->setFields($fields);
    $transform->setDisplay($display);
    return $transform;
  }

  /**
   * Create an EntityTransform from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be transformed.
   * @param string $transform_mode
   *   (Optional) The transform mode to be used for transformation.
   *
   * @return EntityTransform
   *   An EntityTransform based on the entity.
   */
  public static function createFromEntity(EntityInterface $entity, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    return new self($entity->getEntityTypeId(), $entity->id(), $transform_mode, $entity->language()->getId());
  }

  /**
   * Create an EntityTransform from multiple entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities to be transformed.
   * @param string $transform_mode
   *   (Optional) The transform mode to be used for transformation.
   *
   * @return EntityTransform
   *   An EntityTransform based on the entities.
   */
  public static function createFromMultipleEntities(array $entities, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    $ids = [];
    $entityTypeId = '';
    $langcode = NULL;
    foreach ($entities as $entity) {
      $ids[] = $entity->id();
      $entityTypeId = $entity->getEntityTypeId();
      $langcode = $entity->language()->getId();
    }
    return new self($entityTypeId, $ids, $transform_mode, $langcode);
  }

}
