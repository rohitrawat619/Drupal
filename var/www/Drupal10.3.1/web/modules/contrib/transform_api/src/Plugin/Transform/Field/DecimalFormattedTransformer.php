<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'number_decimal' transformer.
 *
 * The 'Formatted' transformer is different for integer fields on the one hand,
 * and for decimal and float fields on the other hand, in order to be able to
 * use different settings.
 *
 * @FieldTransform(
 *   id = "number_decimal",
 *   label = @Translation("Decimal formatted"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class DecimalFormattedTransformer extends NumericTransformerBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thousand_separator' => '',
      'decimal_separator' => '.',
      'scale' => 2,
      'prefix_suffix' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['decimal_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decimal marker'),
      '#options' => ['.' => $this->t('Decimal point'), ',' => $this->t('Comma')],
      '#default_value' => $this->getSetting('decimal_separator'),
      '#weight' => 5,
    ];
    $elements['scale'] = [
      '#type' => 'number',
      '#title' => $this->t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $this->getSetting('scale'),
      '#description' => $this->t('The number of digits to the right of the decimal.'),
      '#weight' => 6,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, $this->getSetting('scale'), $this->getSetting('decimal_separator'), $this->getSetting('thousand_separator'));
  }

}
