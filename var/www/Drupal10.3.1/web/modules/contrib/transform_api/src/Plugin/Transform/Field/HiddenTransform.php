<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for hidden fields.
 *
 * @FieldTransform(
 *  id = "hidden",
 *  label = @Translation("Hidden"),
 *  field_types = {
 *  }
 * )
 */
class HiddenTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    return [];
  }

}
