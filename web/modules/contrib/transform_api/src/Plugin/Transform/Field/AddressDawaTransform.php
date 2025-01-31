<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for address DAWA field types.
 *
 * @FieldTransform(
 *  id = "address_dawa",
 *  label = @Translation("Address DAWA"),
 *  field_types = {
 *    "address_dawa"
 *  }
 * )
 */
class AddressDawaTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      $result = $item->getValue();
      unset($result['data']);
      $values[] = $result;
    }
    return $values;
  }

}
