<?php

namespace Drupal\transform_api;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a transform block entity.
 */
interface TransformBlockInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\transform_api\TransformBlockPluginInterface
   *   The plugin instance for this block.
   */
  public function getPlugin();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this block.
   */
  public function getPluginId();

  /**
   * Returns the region this block is placed in.
   *
   * @return string
   *   The region this block is placed in.
   */
  public function getRegion();

  /**
   * Returns an array of visibility condition configurations.
   *
   * @return array
   *   An array of visibility condition configuration keyed by the condition ID.
   */
  public function getVisibility();

  /**
   * Gets conditions for this block.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  public function getVisibilityConditions();

  /**
   * Gets a visibility condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getVisibilityCondition($instance_id);

  /**
   * Sets the visibility condition configuration.
   *
   * @param string $instance_id
   *   The condition instance ID.
   * @param array $configuration
   *   The condition configuration.
   *
   * @return $this
   */
  public function setVisibilityConfig($instance_id, array $configuration);

  /**
   * Returns the weight of this block (used for sorting).
   *
   * @return int
   *   The block weight.
   */
  public function getWeight();

  /**
   * Sets the region this block is placed in.
   *
   * @param string $region
   *   The region to place this block in.
   *
   * @return $this
   */
  public function setRegion($region);

  /**
   * Sets the block weight.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Creates a duplicate of the transform block entity.
   *
   * @param string $new_id
   *   (optional) The new ID on the duplicate block.
   *
   * @return static
   *   A clone of $this with all identifiers unset, so saving it inserts a new
   *   entity into the storage system.
   */
  public function createDuplicateBlock($new_id = NULL);

}
