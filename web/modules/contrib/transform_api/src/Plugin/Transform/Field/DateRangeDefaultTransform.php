<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Transform field plugin for daterange field types.
 *
 * @FieldTransform(
 *  id = "daterange_default",
 *  label = @Translation("Default"),
 *  field_types = {
 *    "daterange"
 *  }
 * )
 */
class DateRangeDefaultTransform extends DateTimeDefaultTransform {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        $elements[] = [
          'type' => 'daterange',
          'start_date' => $this->buildDate($item->start_date),
          'end_date' => $this->buildDate($item->end_date),
          'separator' => ' ' . $this->getSetting('separator') . ' ',
        ];
      }
    }

    return $elements;
  }

}
