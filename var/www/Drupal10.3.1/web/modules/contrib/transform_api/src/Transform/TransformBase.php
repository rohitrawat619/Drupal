<?php

namespace Drupal\transform_api\Transform;

use Drupal\Core\Cache\CacheableMetadata;

/**
 * Base class for transforms.
 */
abstract class TransformBase extends CacheableMetadata implements TransformInterface {

  /**
   * The values of the transform.
   *
   * @var array
   */
  protected $values = [];

  /**
   * {@inheritdoc}
   */
  public function getAlterIdentifiers() {
    return [$this->getTransformType()];
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($key) {
    return $this->values[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function setValues($values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($key, $value) {
    $this->values[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    $keys = [$this->getTransformType()];
    foreach ($this->getValues() as $value) {
      if (is_array($value)) {
        $keys[] = serialize($value);
      }
      else {
        $keys[] = strval($value);
      }
    }
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldBeCached() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return FALSE;
  }

}
