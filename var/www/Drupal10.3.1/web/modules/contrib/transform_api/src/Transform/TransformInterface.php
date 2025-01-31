<?php

namespace Drupal\transform_api\Transform;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Interface for a transform.
 */
interface TransformInterface extends CacheableDependencyInterface {

  /**
   * Returns the transform type.
   *
   * @return string
   *   The transform type.
   */
  public function getTransformType();

  /**
   * Return list of identifiers used by alter hooks.
   *
   * @return string[]
   *   The list of identifiers.
   */
  public function getAlterIdentifiers();

  /**
   * Returns all values for the transform.
   *
   * @return array
   *   Array of values.
   */
  public function getValues();

  /**
   * Returns a value from the transform.
   *
   * @param string $key
   *   Key for the desired value.
   *
   * @return mixed
   *   The value requested or NULL if empty.
   */
  public function getValue($key);

  /**
   * Set all values of the transform.
   *
   * @param array $values
   *   Array of values.
   */
  public function setValues(array $values);

  /**
   * Set a value of the transform.
   *
   * @param string $key
   *   The key of the value to set.
   * @param mixed $value
   *   The new value for the key.
   */
  public function setValue($key, $value);

  /**
   * The cache keys associated with this object.
   *
   * @return string[]
   *   A set of cache keys.
   */
  public function getCacheKeys();

  /**
   * The weight of the transform in case of sorting.
   *
   * @return int|null
   *   The weight of the transform if available.
   */
  public function getWeight();

  /**
   * Transform the transform into JSON.
   *
   * Perform the transformation and return it as an array ready to be JSON
   * encoded. Can include caching metadata and other metadata if the key
   * starts with "#".
   *
   * In case the transform is actually multiple transforms wrapped in one,
   * the returned array should be an array of transforms.
   *
   * @return array
   *   JSON array.
   *
   * @throws \Drupal\transform_api\Exception\ResponseTransformationException
   */
  public function transform();

  /**
   * Return whether it is worth caching the transform.
   *
   * Whether it is worth caching the transformation independently or not.
   * Even if FALSE is returned, the transformation can still be cached as
   * part of a greater transformation. In cases where the transformation
   * already holds all the data this is a good option.
   *
   * If a transformation should never be cached, instead set cache max age to 0.
   *
   * @return bool
   *   Whether it is worth caching the transform.
   */
  public function shouldBeCached();

  /**
   * Return whether the transform include multiple items.
   *
   * @return bool
   *   Whether the transform include multiple items.
   */
  public function isMultiple();

}
