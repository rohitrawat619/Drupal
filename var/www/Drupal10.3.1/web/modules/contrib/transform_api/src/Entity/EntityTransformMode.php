<?php

namespace Drupal\transform_api\Entity;

use Drupal\Core\Entity\EntityDisplayModeBase;
use Drupal\transform_api\EntityTransformModeInterface;

/**
 * Defines the entity transform mode configuration entity class.
 *
 * Transform modes allow entity transforms to be displayed differently depending
 * on the context. For instance, the user entity transform can be displayed with
 * a set of fields on the 'profile' page (user edit page) and with a different
 * set of fields (or settings) on the user registration page. Modules taking
 * part in the display of the entity transform (notably the Field API) can
 * adjust their behavior depending on the requested transform mode. An
 * additional 'default' transform mode is available for all entity types. For
 * each available transform mode, administrators can configure whether it should
 * use its own set of field display settings, or just replicate the settings
 * of the 'default' transform mode, thus reducing the amount of transform
 * display configurations to keep track of.
 *
 * @see \Drupal\transform_api\Repository\EntityTransformRepositoryInterface::getAllTransformModes()
 * @see \Drupal\transform_api\Repository\EntityTransformRepositoryInterface::getTransformModes()
 *
 * @ConfigEntityType(
 *   id = "entity_transform_mode",
 *   label = @Translation("Transform mode"),
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "targetEntityType",
 *     "cache",
 *   }
 * )
 */
class EntityTransformMode extends EntityDisplayModeBase implements EntityTransformModeInterface {

}
