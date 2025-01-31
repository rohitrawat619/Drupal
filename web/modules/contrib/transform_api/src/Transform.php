<?php

namespace Drupal\transform_api;

use Drupal\transform_api\Transform\TransformInterface;

/**
 * Transform utility functions.
 */
class Transform {

  /**
   * Return whether value is a transform.
   *
   * @param mixed $value
   *   Value to examine.
   *
   * @return bool
   *   Whether value is a transform.
   */
  public static function isTransform($value) {
    if (is_array($value) && isset($value['#transform'])) {
      return TRUE;
    }
    elseif ($value instanceof TransformInterface) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Identifies the children of an element array, optionally sorted by weight.
   *
   * The children of an element array are those key/value pairs whose key does
   * not start with a '#'. See \Drupal\Core\Render\RendererInterface::render()
   * for details.
   *
   * @param array $elements
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The array keys of the element's children.
   */
  public static function children(array &$elements, $sort = FALSE) {
    // Do not attempt to sort elements which have already been sorted.
    $sort = isset($elements['#sorted']) ? !$elements['#sorted'] : $sort;

    // Filter out properties from the element, leaving only children.
    $count = count($elements);
    $child_weights = [];
    $i = 0;
    $sortable = FALSE;
    foreach ($elements as $key => $value) {
      if (is_int($key) || $key === '' || $key[0] !== '#') {
        if (is_array($value) && isset($value['#transform'])) {
          if (isset($value['#weight'])) {
            $weight = $value['#weight'];
            $sortable = TRUE;
          }
          else {
            $weight = 0;
          }
          // Supports weight with up to three digit precision and conserve
          // the insertion order.
          $child_weights[$key] = floor($weight * 1000) + $i / $count;
        }
        elseif ($value instanceof TransformInterface) {
          if (!is_null($value->getWeight())) {
            $weight = $value->getWeight();
            $sortable = TRUE;
          }
          else {
            $weight = 0;
          }
          $child_weights[$key] = floor($weight * 1000) + $i / $count;
        }
        else {
          $child_weights[$key] = $i / $count;
        }
      }
      $i++;
    }

    // Sort the children if necessary.
    if ($sort && $sortable) {
      asort($child_weights);
      // Put the sorted children back into $elements in the correct order, to
      // preserve sorting if the same element is passed through
      // \Drupal\Core\Render\Element::children() twice.
      foreach ($child_weights as $key => $weight) {
        $value = $elements[$key];
        unset($elements[$key]);
        $elements[$key] = $value;
      }
      $elements['#sorted'] = TRUE;
    }

    return array_keys($child_weights);
  }

  /**
   * Indicates whether the given element is empty.
   *
   * An element that only has #cache set is considered empty, because it will
   * render to the empty string.
   *
   * @param array $elements
   *   The element.
   *
   * @return bool
   *   Whether the given element is empty.
   */
  public static function isEmpty(array $elements) {
    return empty($elements) || (count($elements) === 1 && array_keys($elements) === ['#cache']);
  }

  /**
   * Convert a render array into a transformation array.
   *
   * @param array $elements
   *   The render array.
   */
  public static function renderArrayToTransform(array &$elements) {
    foreach ($elements as $key => $value) {
      if (is_object($value) && method_exists($value, 'toArray')) {
        $value = $value->toArray();
      }
      if (is_string($key) && $key[0] === '#') {
        $elements[substr($key, 1)] = $value;
        unset($elements[$key]);
      }
    }
  }

}
