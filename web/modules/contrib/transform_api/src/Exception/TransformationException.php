<?php

namespace Drupal\transform_api\Exception;

use Drupal\Component\Plugin\Exception\ExceptionInterface;

/**
 * Base class for transformation exceptions.
 */
class TransformationException extends \Exception implements ExceptionInterface {}
