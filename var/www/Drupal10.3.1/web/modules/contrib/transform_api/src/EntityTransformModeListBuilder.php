<?php

namespace Drupal\transform_api;

use Drupal\Core\Url;
use Drupal\field_ui\EntityDisplayModeListBuilder;

/**
 * Defines a class to build a listing of transform mode entities.
 *
 * @see \Drupal\transform_api\Entity\EntityTransformMode
 */
class EntityTransformModeListBuilder extends EntityDisplayModeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    foreach ($build as $entity_type => $table) {
      foreach ($table['#rows']['_add_new'] as $key => $row) {
        $build[$entity_type]['#rows']['_add_new'][$key]['data']['#url'] = Url::fromRoute('entity.entity_transform_mode.add_form', ['entity_type_id' => $entity_type]);
      }
    }
    return $build;
  }

}
