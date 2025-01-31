<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Transform field plugin for file field types as Urls.
 *
 * @FieldTransform(
 *  id = "file_url",
 *  label = @Translation("File URL"),
 *  field_types = {
 *    "file"
 *  }
 * )
 */
class FileURLTransform extends FileTransformBase {

  public static function defaultSettings() {
    return [
      'absolute' => FALSE,
    ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Absolute URL'),
      '#default_value' => $this->getSetting('absolute'),
    ];

    return $form;
  }

  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $url_format = $this->getSetting('absolute') ? 'Absolute' : 'Relative';

    $summary[] = $this->t('URL Format: @url_format', ['@url_format' => $url_format]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (!empty($item->getValue()['target_id'])) {
        // File ID.
        $fid = $item->getValue()['target_id'];
        // Load file.
        $file = $this->loadFile($fid);

        $values[] = $this->getFileUrl($file);
      }
      else {
        $values[] = NULL;
      }
    }
    return $values;
  }

  protected function getFileUrl($file) {
    $uri = $file->getFileUri();

    if ($this->getSetting('absolute')) {
      return $this->fileUrlGenerator->generateAbsoluteString($uri);
    }

    return $this->fileUrlGenerator->generateString($uri);
  }

}
