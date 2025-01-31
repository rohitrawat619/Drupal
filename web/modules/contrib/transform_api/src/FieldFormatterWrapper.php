<?php

namespace Drupal\transform_api;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Wrapper for adapting a field formatter into a field transformer.
 *
 * @ingroup field_transformer
 */
trait FieldFormatterWrapper {

  /**
   * {@inheritdoc}
   */
  public function getTransformMode() {
    return $this->viewMode;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareTransform(array $entities_items) {
    /** @var \Drupal\Core\Field\FormatterInterface $formatter */
    $formatter = $this;
    $formatter->prepareView($entities_items);
  }

  /**
   * {@inheritdoc}
   */
  public function transform(FieldItemListInterface $items, $langcode = NULL) {
    $transform = $this->view($items, $langcode);

    $transform['#transform'] = $transform['#theme'] ?? '';
    unset($transform['#theme']);
    $transform['#collapse'] = !($transform['#is_multiple'] ?? FALSE);

    return $transform;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return $this->transformElements($items, $langcode);
  }

}
