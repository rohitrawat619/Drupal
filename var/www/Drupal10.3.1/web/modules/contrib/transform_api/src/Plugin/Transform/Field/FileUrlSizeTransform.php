<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Transform field plugin for file field types as Urls.
 *
 * @FieldTransform(
 *  id = "file_url_size",
 *  label = @Translation("File URL and Size"),
 *  field_types = {
 *    "file"
 *  }
 * )
 */
class FileUrlSizeTransform extends FileURLTransform {

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

        $values[] = [
          'url' => $this->getFileUrl($file),
          'filesize' => $file->getSize(),
        ];
      }
      else {
        $values[] = NULL;
      }
    }
    return $values;
  }

}
