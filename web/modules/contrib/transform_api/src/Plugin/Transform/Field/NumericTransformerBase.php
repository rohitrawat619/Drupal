<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Parent plugin for decimal and integer formatters.
 */
abstract class NumericTransformerBase extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [
      ''  => $this->t('- None -'),
      '.' => $this->t('Decimal point'),
      ',' => $this->t('Comma'),
      ' ' => $this->t('Space'),
      chr(8201) => $this->t('Thin space'),
      "'" => $this->t('Apostrophe'),
    ];
    $elements['thousand_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $this->getSetting('thousand_separator'),
      '#weight' => 0,
    ];

    $elements['prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display prefix and suffix'),
      '#default_value' => $this->getSetting('prefix_suffix'),
      '#weight' => 10,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->numberFormat(1234.1234567890);
    if ($this->getSetting('prefix_suffix')) {
      $summary[] = $this->t('Display with prefix and suffix.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    $settings = $this->getFieldSettings();

    foreach ($items as $delta => $item) {
      $output = $this->numberFormat($item->value);

      // Account for prefix and suffix.
      if ($this->getSetting('prefix_suffix')) {
        $prefixes = isset($settings['prefix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['prefix'])) : [''];
        $suffixes = isset($settings['suffix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['suffix'])) : [''];
        $prefix = (count($prefixes) > 1) ? $this->formatPlural($item->value, $prefixes[0], $prefixes[1]) : $prefixes[0];
        $suffix = (count($suffixes) > 1) ? $this->formatPlural($item->value, $suffixes[0], $suffixes[1]) : $suffixes[0];
        $output = $prefix . $output . $suffix;
      }

      $values[$delta] = $output;
    }

    return $values;
  }

  /**
   * Formats a number.
   *
   * @param mixed $number
   *   The numeric value.
   *
   * @return string
   *   The formatted number.
   */
  abstract protected function numberFormat($number);

}
