<?php

namespace Drupal\transform_api\Transform;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * A transform for an entity autocomplete endpoint.
 */
class EntityAutocompleteEndpointTransform extends TransformBase {

  public function __construct($target_type, $selection_handler = 'default', $selection_settings = []) {
    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $data = serialize($selection_settings) . $target_type . $selection_handler;
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $this->values = [
      'target_type' => $target_type,
      'selection_handler' => $selection_handler,
      'selection_settings_key' => $selection_settings_key,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getTransformType() {
    return 'entity_autocomplete_endpoint';
  }

  /**
   * {@inheritDoc}
   */
  public function transform() {
    if (empty($this->values)) {
      return [];
    }
    else {
      $url = Url::fromRoute('transform_api.entity_autocomplete', $this->getValues());
      return [
        '#collapse' => TRUE,
        'endpoint' => $url->toString(),
      ];
    }
  }

}
