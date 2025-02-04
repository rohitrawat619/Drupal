<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Transform field plugin for datetime field types with simple formatting.
 *
 * @FieldTransform(
 *  id = "datetime_simple",
 *  label = @Translation("Simple"),
 *  field_types = {
 *    "datetime"
 *  }
 * )
 */
class DateTimeSimpleTransform extends DateTimeTransformBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format_type' => 'medium',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format_type = $this->getSetting('format_type');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $time = new DrupalDateTime();
    $format_types = $this->dateFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->getTimestamp(), $type);
      $options[$type] = $type_info->label() . ' (' . $format . ')';
    }

    $form['format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#description' => $this->t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date = new DrupalDateTime();
    $summary[] = $this->t('Format: @display', ['@display' => $this->formatDate($date)]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $item) {
      if ($item->date) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;
        $elements[] = $this->buildDate($date);
      }
    }

    return $elements;
  }

}
