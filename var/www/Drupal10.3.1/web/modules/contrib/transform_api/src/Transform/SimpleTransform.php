<?php

namespace Drupal\transform_api\Transform;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * A simple transform that just contains a JSON array.
 */
class SimpleTransform extends TransformBase {

  /**
   * Content which is already a JSON array.
   *
   * @var array
   */
  protected array $content = [];

  /**
   * Construct a SimpleTransform.
   */
  public function __construct(array $content) {
    $this->content = $content;
    $this->setCacheability(CacheableMetadata::createFromRenderArray($content));
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'simple';
  }

  /**
   * {@inheritdoc}
   */
  public function shouldBeCached() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    return $this->content;
  }

}
