<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for simple field types.
 *
 * @FieldTransform(
 *  id = "value",
 *  label = @Translation("String value"),
 *  field_types = {
 *    "string",
 *    "string_long",
 *    "list_string",
 *    "integer",
 *    "float",
 *    "decimal",
 *    "telephone",
 *    "email"
 *  }
 * )
 */
class ValueTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (isset($item->getValue()['value'])) {
        $values[] = $item->getValue()['value'] ?? NULL;
      }
    }
    return $values;
  }

}
