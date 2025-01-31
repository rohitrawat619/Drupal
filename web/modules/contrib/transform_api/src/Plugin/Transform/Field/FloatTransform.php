<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for float field types.
 *
 * @FieldTransform(
 *  id = "float",
 *  label = @Translation("Float value"),
 *  field_types = {
 *    "float",
 *    "decimal"
 *  }
 * )
 */
class FloatTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (isset($item->getValue()['value'])) {
        $values[] = floatval($item->getValue()['value']) ?? NULL;
      }
    }
    return $values;
  }

}
