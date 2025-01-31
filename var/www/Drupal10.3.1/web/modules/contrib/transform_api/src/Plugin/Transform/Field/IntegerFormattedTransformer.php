<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

/**
 * Plugin implementation of the 'number_integer' transformer.
 *
 * The 'Formatted' transformer is different for integer fields on the one hand,
 * and for decimal and float fields on the other hand, in order to be able to
 * use different settings.
 *
 * @FieldTransform(
 *   id = "number_integer",
 *   label = @Translation("Integer formatted"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class IntegerFormattedTransformer extends NumericTransformerBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thousand_separator' => '',
      'prefix_suffix' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, 0, '', $this->getSetting('thousand_separator'));
  }

}
