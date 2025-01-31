<?php

namespace Drupal\transform_api\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the transform block deletion form.
 */
class TransformBlockDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('transform_api.transform_block.admin_display');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\transform_api\TransformBlockInterface $entity */
    $entity = $this->getEntity();
    /** @var \Drupal\transform_api\TransformBlocks $transform_blocks */
    $transform_blocks = \Drupal::service('transform_api.transform_blocks');
    $regions = $transform_blocks->getRegions();
    return $this->t('Are you sure you want to remove the @entity-type %label from the %region region?', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label(),
      '%region' => $regions[$entity->getRegion()],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will remove the block placement. You will need to <a href=":url">place it again</a> in order to undo this action.', [
      ':url' => Url::fromRoute('transform_api.transform_block.admin_display')->toString(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    /** @var \Drupal\transform_api\TransformBlocks $transform_blocks */
    $transform_blocks = \Drupal::service('transform_api.transform_blocks');
    $regions = $transform_blocks->getRegions();
    return $this->t('The @entity-type %label has been removed from the %region region.', [
      '@entity-type' => $entity->getEntityType()->getSingularLabel(),
      '%label' => $entity->label(),
      '%region' => $regions[$entity->getRegion()],
    ]);
  }

}
