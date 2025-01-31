<?php

namespace Drupal\transform_api\Plugin\Transform\SectionComponent;

use Drupal\layout_builder\SectionComponent;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\SectionComponentTransformBase;
use Drupal\transform_api\Transform\EntityTransform;

/**
 * Section component plugin for block content blocks.
 *
 * @SectionComponentTransform(
 *  id = "block_content",
 *  title = "Block Content"
 * )
 */
class BlockContent extends SectionComponentTransformBase {

  /**
   * {@inheritdoc}
   */
  public function transform(SectionComponent $component, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    /** @var \Drupal\block_content\Plugin\Block\BlockContentBlock $blockContentPlugin */
    $blockContentPlugin = $component->getPlugin();
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $entity = $entityRepository->loadEntityByUuid('block_content', $blockContentPlugin->getDerivativeId());
    return EntityTransform::createFromEntity($entity, $transform_mode);
  }

}
