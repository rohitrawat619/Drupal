<?php

namespace Drupal\transform_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for handling transform mode selections.
 */
class EntityDisplayModeController extends ControllerBase {

  /**
   * Provides a list of eligible entity types for adding transform modes.
   *
   * @return array
   *   A list of entity types to add a transform mode for.
   */
  public function transformModeTypeSelection() {
    $entity_types = [];
    foreach ($this->entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route') && $entity_type->hasViewBuilderClass()) {
        $entity_types[$entity_type_id] = [
          'title' => $entity_type->getLabel(),
          'url' => Url::fromRoute('entity.entity_transform_mode.add_form', ['entity_type_id' => $entity_type_id]),
          'localized_options' => [],
        ];
      }
    }
    return [
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    ];
  }

}
