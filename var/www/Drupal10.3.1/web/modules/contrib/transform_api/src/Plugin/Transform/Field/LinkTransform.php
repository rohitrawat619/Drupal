<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\transform_api\FieldTransformBase;

/**
 * Transform field plugin for link field types.
 *
 * @FieldTransform(
 *  id = "link",
 *  label = @Translation("Link"),
 *  field_types = {
 *    "link"
 *  }
 * )
 */
class LinkTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      if (!empty($item->getValue())) {
        $link = [];
        if (!empty($item->getValue()['title'])) {
          $link['title'] = $item->getValue()['title'];
        }
        if (!empty($item->getValue()['uri'])) {
          $url = Url::fromUri($item->getValue()['uri']);
          $link['url'] = $url->toString();
        }
        $values[] = $link;
      }
    }
    return $values;
  }

}
