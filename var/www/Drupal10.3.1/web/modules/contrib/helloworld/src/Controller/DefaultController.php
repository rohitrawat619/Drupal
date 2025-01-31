<?php

namespace Drupal\helloworld\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Hello.
   *
   * @return array
   *   Return Hello string.
   */
  public function hello() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Hello World (Example Drupal 8 Controller) code:</h2>')
      . '<code><?php<br>namespace Drupal\helloworld\Controller;<br>'
      . 'use Drupal\Core\Controller\ControllerBase;<br><br>'
      . 'class DefaultController extends ControllerBase {<br>'
      . '&nbsp;&nbsp;public function hello() {<br>'
      . '&nbsp;&nbsp;&nbsp;&nbsp;return [<br>'
      . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\'#type\' => \'markup\',<br>'
      . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\'#markup\' => $this->t(\'Hello World (Example Drupal 8 Controller) code!\')<br>'
      . '&nbsp;&nbsp;&nbsp;&nbsp;];<br>&nbsp;&nbsp;}<br>}</code>',
    ];
  }

}