<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for integer field types.
 *
 * @FieldTransform(
 *  id = "integer",
 *  label = @Translation("Integer value"),
 *  field_types = {
 *    "integer",
 *    "float",
 *    "decimal"
 *  }
 * )
 */
class IntegerTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (isset($item->getValue()['value'])) {
        $values[] = intval($item->getValue()['value']) ?? NULL;
      }
    }
    return $values;
  }

}
