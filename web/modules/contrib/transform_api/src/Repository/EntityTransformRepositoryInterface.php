<?php

namespace Drupal\transform_api\Repository;

/**
 * Interface for transform mode repository.
 */
interface EntityTransformRepositoryInterface {

  /**
   * The default display mode ID.
   *
   * @var string
   */
  const DEFAULT_DISPLAY_MODE = 'default';

  /**
   * Gets the entity transform mode info for all entity types.
   *
   * @return array
   *   The transform mode info for all entity types.
   */
  public function getAllTransformModes();

  /**
   * Gets the entity transform mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose transform mode info should be returned.
   *
   * @return array
   *   The transform mode info for a specific entity type.
   */
  public function getTransformModes($entity_type_id);

  /**
   * Gets an array of transform mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose transform mode options should be returned.
   *
   * @return array
   *   An array of transform mode labels, keyed by the display mode ID.
   */
  public function getTransformModeOptions($entity_type_id);

  /**
   * Returns an array of enabled transform mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose transform mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of transform mode labels, keyed by the display mode ID.
   */
  public function getTransformModeOptionsByBundle($entity_type_id, $bundle);

  /**
   * Clears the gathered display mode info.
   *
   * @return $this
   */
  public function clearDisplayModeInfo();

  /**
   * Returns the transform display associated with a bundle and transform mode.
   *
   * Use this function when assigning suggested display options for a component
   * in a given transform mode. Note that they will only be actually used at
   * render time if the transform mode itself is configured to use dedicated
   * display settings for the bundle; if not, the 'default' display is used
   * instead.
   *
   * The function reads the entity transform display from the current
   * configuration, or returns a ready-to-use empty one if configuration entry
   * exists yet for this bundle and transform mode. This streamlines
   * manipulation of display objects by always returning a consistent object
   * that reflects the current state of the configuration.
   *
   * Example usage:
   * - Set the 'body' field to be displayed and the 'field_image' field to be
   *   hidden on article nodes in the 'default' display.
   * @code
   * \Drupal::service('entity_display.repository')
   *   ->gettransformDisplay('node', 'article', 'default')
   *   ->setComponent('body', [
   *     'type' => 'text_summary_or_trimmed',
   *     'settings' => ['trim_length' => '200']
   *     'weight' => 1,
   *   ])
   *   ->removeComponent('field_image')
   *   ->save();
   * @endcode
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $transform_mode
   *   (optional) The transform mode. Defaults to self::DEFAULT_DISPLAY_MODE.
   *
   * @return \Drupal\transform_api\Configs\EntityTransformDisplayInterface
   *   The entity transform display associated with the transform mode.
   */
  public function getTransformDisplay($entity_type, $bundle, $transform_mode = self::DEFAULT_DISPLAY_MODE);

}
