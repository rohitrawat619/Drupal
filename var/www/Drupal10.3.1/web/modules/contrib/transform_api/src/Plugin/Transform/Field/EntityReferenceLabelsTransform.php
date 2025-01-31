<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Entity\EntityInterface;

/**
 * Transform field plugin for entity reference field types as links.
 *
 * @FieldTransform(
 *  id = "entity_reference_labels",
 *  label = @Translation("Entity reference labels"),
 *  field_types = {
 *    "entity_reference"
 *  }
 * )
 */
class EntityReferenceLabelsTransform extends EntityReferenceLinksTransform {

  /**
   * {@inheritdoc}
   */
  protected function transformEntity(EntityInterface $entity) {
    return $entity->label();
  }

}
