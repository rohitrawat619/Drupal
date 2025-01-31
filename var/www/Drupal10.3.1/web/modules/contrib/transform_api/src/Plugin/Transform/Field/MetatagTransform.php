<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for metatag field types.
 *
 * @FieldTransform(
 *  id = "metatag",
 *  label = @Translation("Meta tags"),
 *  field_types = {
 *    "metatag"
 *  }
 * )
 */
class MetatagTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach (metatag_get_tags_from_route($items->getEntity())['#attached'] ?? [] as $section => $tags) {
      $values[$section] = [];
      foreach ($tags as $item) {
        $tag = [];
        foreach ($item[0] as $key => $value) {
          $key = substr($key, 1);
          $tag[$key] = $value;
        }
        $values[$section][$item[1]] = $tag;
      }
    }
    if (!empty($values)) {
      $values['#collapse'] = FALSE;
    }
    return $values;
  }

}
