<?php

namespace Drupal\transform_api\Transform;

/**
 * A transform for request paths.
 */
class RequestPathTransform extends PluginTransformBase {

  /**
   * Construct a RequestPathTransform.
   *
   * @param string $url
   *   Url that needs to be transformed.
   * @param string $region
   *   (Optional) Only transform this region, otherwise transform them all.
   */
  public function __construct($url, $region = '') {
    $this->values = [
      'url' => $url,
      'region' => $region,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformType() {
    return 'request_path';
  }

}
