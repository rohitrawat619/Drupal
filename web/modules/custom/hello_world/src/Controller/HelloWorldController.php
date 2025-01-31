<?php

namespace Drupal\hello_world\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Hello World routes.
 */
class HelloWorldController extends ControllerBase {

  /**
   * Returns a simple page with 'Hello World'.
   *
   * @return array
   *   A render array containing the 'Hello World' text.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello World'),
    ];
  }

}