<?php

namespace Drupal\transform_api\Transform;

use Drupal\transform_api\Entity\TransformBlock;
use Drupal\transform_api\TransformBlockInterface;

/**
 * A transform for a transform block.
 */
class BlockTransform extends PluginTransformBase {

  /**
   * The transform block to transform.
   *
   * @var \Drupal\transform_api\TransformBlockInterface|null
   */
  protected ?TransformBlockInterface $block = NULL;

  public function __construct($id) {
    $this->values = [
      'id' => $id,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getTransformType() {
    return 'block';
  }

  /**
   * Set the transform block to be transformed.
   *
   * @param \Drupal\transform_api\TransformBlockInterface $block
   *   The transform block to be transformed.
   */
  public function setBlock(TransformBlockInterface $block) {
    $this->block = $block;
  }

  /**
   * Return the transform block to be transformed.
   *
   * @return \Drupal\transform_api\TransformBlockInterface|null
   *   The transform block or NULL if not found.
   */
  public function getBlock(): TransformBlockInterface|null {
    if (empty($this->block)) {
      $this->block = TransformBlock::load($this->getValue('id'));
    }
    return $this->block;
  }

  /**
   * Create a BlockTransform from a transform block.
   *
   * @param \Drupal\transform_api\TransformBlockInterface $block
   *   The transform block to be transformed.
   *
   * @return \Drupal\transform_api\Transform\BlockTransform
   *   The BlockTransform object.
   */
  public static function createFromBlock(TransformBlockInterface $block) {
    $transform = new self($block->id());
    $transform->setBlock($block);
    return $transform;
  }

}
