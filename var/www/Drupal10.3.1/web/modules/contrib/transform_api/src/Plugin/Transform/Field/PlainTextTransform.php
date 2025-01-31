<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for text field types as plain text.
 *
 * @FieldTransform(
 *  id = "plain_text",
 *  label = @Translation("Plain text"),
 *  field_types = {
 *    "text_format",
 *    "text_long",
 *    "text_with_summary"
 *  }
 * )
 */
class PlainTextTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'text_length' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'text_length' => [
        '#type' => 'number',
        '#title' => $this->t('Text length'),
        '#description' => $this->t('Sets a maximum text length. Leave blank or 0 for unlimited length.'),
        '#default_value' => $this->getSetting('text_length'),
        '#required' => FALSE,
      ],

        // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (isset($item->getValue()['value'])) {
        $text = PlainTextOutput::renderFromHtml($item->getValue()['value'] ?? '');
        if (!empty($this->getSetting('text_length'))) {
          $text = Unicode::truncate($text, $this->getSetting('text_length'), TRUE, TRUE);
        }
        $values[] = $text;
      }
    }
    return $values;
  }

}
