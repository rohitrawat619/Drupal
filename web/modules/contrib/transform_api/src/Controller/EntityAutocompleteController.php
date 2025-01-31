<?php

namespace Drupal\transform_api\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Site\Settings;
use Drupal\system\Controller\EntityAutocompleteController as SystemEntityAutocompleteController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for handling entity autocomplete, but transformed.
 */
class EntityAutocompleteController extends SystemEntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request, $target_type, $selection_handler, $selection_settings_key) {
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $tag_list = Tags::explode($input);
      $typed_string = !empty($tag_list) ? mb_strtolower(array_pop($tag_list)) : '';

      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);
      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler, Settings::getHashSalt());
        if (!hash_equals($selection_settings_hash, $selection_settings_key)) {
          // Disallow access when the selection settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      $matches = $this->matcher->getMatches($target_type, $selection_handler, $selection_settings, $typed_string);
    }
    $result = [];
    foreach ($matches as $match) {
      $match['value'] = EntityAutocomplete::extractEntityIdFromAutocompleteInput($match['value']);
      $result[] = $match;
    }

    return new JsonResponse($result);
  }

}
