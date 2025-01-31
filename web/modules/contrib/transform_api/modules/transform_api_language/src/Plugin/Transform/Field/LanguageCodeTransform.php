<?php

namespace Drupal\transform_api_language\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for language field types.
 *
 * @FieldTransform(
 *  id = "language_code",
 *  label = @Translation("Language code"),
 *  field_types = {
 *    "language"
 *  }
 * )
 */
class LanguageCodeTransform extends FieldTransformBase {

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
