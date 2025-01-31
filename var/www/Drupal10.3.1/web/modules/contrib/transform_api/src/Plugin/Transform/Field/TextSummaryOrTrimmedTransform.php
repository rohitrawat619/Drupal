<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Transform field plugin for text with summary.
 *
 * @FieldTransform(
 *  id = "text_summary_or_trimmed",
 *  label = @Translation("Summary or trimmed"),
 *  field_types = {
 *     "text",
 *     "text_format",
 *     "text_long",
 *     "text_with_summary"
 *  }
 * )
 */
class TextSummaryOrTrimmedTransform extends TextFormatTransform {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '600',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['trim_length'] = [
      '#title' => $this->t('Trimmed limit'),
      '#type' => 'number',
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#description' => $this->t('If the summary is not set, the trimmed %label field will end at the last full sentence before this character limit.', ['%label' => $this->fieldDefinition->getLabel()]),
      '#min' => 1,
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Trimmed limit: @trim_length characters', ['@trim_length' => $this->getSetting('trim_length')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];

    // Logic copied over from TextTrimmedFormatter::viewElements().
    $render_as_summary = function(&$element) {
      // Make sure any default #pre_render callbacks are set on the element,
      // because text_pre_render_summary() must run last.
      $element += \Drupal::service('element_info')->getInfo($element['#type']);
      // Add the #pre_render callback that renders the text into a summary.
      $element['#pre_render'][] = [TextTrimmedFormatter::class, 'preRenderSummary'];
      // Pass on the trim length to the #pre_render callback via a property.
      $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
    };

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (!empty($item->getValue()['value'])) {
        $build = [
          '#type' => 'processed_text',
          '#text' => NULL,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];

        if (!empty($item->summary)) {
          $build['#text'] = $item->summary;
        }
        else {
          $build['#text'] = $item->value;
          $render_as_summary($build);
        }

        $render = $this->renderer->renderPlain($build);
        $values[] = $render;
      }
    }
    return $values;
  }

}
