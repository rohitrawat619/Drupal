<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for boolean field types.
 *
 * @FieldTransform(
 *  id = "boolean",
 *  label = @Translation("Boolean"),
 *  field_types = {
 *    "boolean"
 *  }
 * )
 */
class BooleanTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (!empty($item->getValue())) {
        $values[] = boolval($item->getValue()['value'] ?? NULL);
      }
    }
    return $values;
  }

}
