<?php

namespace Drupal\transform_api\Plugin\Transform\SectionComponent;

use Drupal\layout_builder\SectionComponent;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\SectionComponentTransformBase;
use Drupal\transform_api\Transform\EntityTransform;

/**
 * Section component plugin for inline blocks.
 *
 * @SectionComponentTransform(
 *  id = "inline_block",
 *  title = "Inline Block"
 * )
 */
class InlineBlock extends SectionComponentTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transform(SectionComponent $component, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    /** @var \Drupal\layout_builder\Plugin\Block\InlineBlock $inlineBlockPlugin */
    $inlineBlockPlugin = $component->getPlugin();
    $configuration = $inlineBlockPlugin->getConfiguration();
    $entity = \Drupal::entityTypeManager()->getStorage('block_content')->loadRevision($configuration['block_revision_id']);
    return EntityTransform::createFromEntity($entity, $transform_mode);
  }

}
