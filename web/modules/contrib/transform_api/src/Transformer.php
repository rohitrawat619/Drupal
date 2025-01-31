<?php

namespace Drupal\transform_api;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Security\DoTrustedCallbackTrait;
use Drupal\transform_api\EventSubscriber\TransformationCache;
use Drupal\transform_api\Transform\PluginTransformBase;
use Drupal\transform_api\Transform\TransformInterface;

/**
 * The main transformer service.
 *
 * Analog to the renderer service.
 */
class Transformer {

  use DoTrustedCallbackTrait;

  /**
   * The transformation type manager.
   *
   * @var TransformationTypeManager
   */
  protected $transformationTypeManager;

  /**
   * The transformation caching service.
   *
   * @var \Drupal\transform_api\EventSubscriber\TransformationCache
   */
  protected $transformationCache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The controller resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected ControllerResolverInterface $controllerResolver;

  protected const RESERVED_WORDS = ['#cache', '#collapse', '#lazy_transformer'];

  /**
   * Constructs a Transformer service.
   *
   * @param TransformationTypeManager $transformationTypeManager
   *   The transformation type manager.
   * @param \Drupal\transform_api\EventSubscriber\TransformationCache $transformationCache
   *   The transformation caching service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver service.
   */
  public function __construct(TransformationTypeManager $transformationTypeManager, TransformationCache $transformationCache, ModuleHandlerInterface $moduleHandler, ControllerResolverInterface $controller_resolver) {
    $this->transformationTypeManager = $transformationTypeManager;
    $this->transformationCache = $transformationCache;
    $this->moduleHandler = $moduleHandler;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Take a transform, transform it into JSON and then clean it up.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $transform
   *   The transform to transform.
   * @param bool $cleanup
   *   Whether to clean up unnecessary control keys in the transformation
   *   arrays. Set to FALSE for debugging purposes.
   *
   * @return array
   *   An array representing JSON.
   *
   * @throws \Drupal\transform_api\Exception\ResponseTransformationException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function transformRoot(TransformInterface $transform, $cleanup = TRUE) {
    $transformation = $this->transform($transform);
    if ($cleanup) {
      $this->cleanupCacheMetadata($transformation);
    }
    return $transformation;
  }

  /**
   * Take a transform and transform it into JSON.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $transform
   *   The transform to transform.
   *
   * @return array
   *   An array representing JSON.
   *
   * @throws \Drupal\transform_api\Exception\ResponseTransformationException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function transform(TransformInterface $transform) {
    $transformation = FALSE;
    if ($transform->shouldBeCached()) {
      $transformation = $this->transformationCache->get($transform);
    }
    if ($transformation === FALSE) {
      if ($transform instanceof PluginTransformBase) {
        $plugin = $this->transformationTypeManager
          ->createInstance($transform->getTransformType(), $transform->getValues());
        $transformation = $plugin->transform($transform);
      }
      else {
        $transformation = $transform->transform();
      }

      if (!isset($transformation['#access']) && isset($transformation['#access_callback'])) {
        $transformation['#access'] = $this->doCallback('#access_callback', $transformation['#access_callback'], [$transformation]);
      }

      // Early-return nothing if user does not have access.
      if (isset($transformation['#access'])) {
        // If #access is an AccessResultInterface object, we must apply its
        // cacheability metadata to the transformation array.
        if ($transformation['#access'] instanceof AccessResultInterface) {
          $this->addCacheableDependency($transformation, $transformation['#access']);
          if (!$transformation['#access']->isAllowed()) {
            return [];
          }
        }
        elseif ($transformation['#access'] === FALSE) {
          return [];
        }
      }

      /*
       * Make any final changes to the transformation before it is transformed.
       * This means that the $transformation or the children can be altered or
       * corrected before the element is transformed into the final JSON.
       */
      if (isset($transformation['#pre_transform'])) {
        foreach ($transformation['#pre_transform'] as $callable) {
          $transformation = $this->doCallback('#pre_transform', $callable, [$transformation]);
        }
      }

      if (!$transform->isMultiple()) {
        $hooks = ['transform'];
        foreach ($transform->getAlterIdentifiers() as $type) {
          $hooks[] = $type . '_transform';
        }
        $this->moduleHandler->alter($hooks, $transformation);
        $metadata = CacheableMetadata::createFromRenderArray($transformation);
      }
      else {
        $metadata = new CacheableMetadata();
      }

      $this->searchAndTransform($transformation, $metadata);
      $metadata->applyTo($transformation);
      $this->cleanup($transformation);

      if ($transform->shouldBeCached()) {
        $this->transformationCache->saveOnTerminate($transform, $transformation);
      }
    }

    return $transformation;
  }

  /**
   * Search for transforms in array and transform them.
   *
   * @param array $transformation
   *   Transformation array to search in.
   * @param \Drupal\Core\Cache\CacheableMetadata $metadata
   *   Cacheable metadata collected through the array.
   *
   * @throws \Drupal\transform_api\Exception\ResponseTransformationException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function searchAndTransform(array &$transformation, CacheableMetadata &$metadata) {
    foreach ($transformation as $key => $value) {
      if ($value instanceof TransformInterface) {
        $transformation[$key] = $this->transform($value);
        $metadata = $metadata->merge(CacheableMetadata::createFromRenderArray($transformation[$key]));
      }
      elseif (is_array($value)) {
        $this->searchAndTransform($transformation[$key], $metadata);
        if (isset($value['#cache'])) {
          $metadata = $metadata->merge(CacheableMetadata::createFromRenderArray($transformation[$key]));
        }
      }
    }
  }

  /**
   * Cleanup cache metadata after transformation.
   *
   * @param array $transformation
   *   JSON array needing to be cleaned.
   */
  public function cleanupCacheMetadata(array &$transformation) {
    if (isset($transformation['#lazy_transformer'])) {
      $this->handleLazyTransformers($transformation);
    }
    $collapse = $transformation['#collapse'] ?? FALSE;
    unset($transformation['#cache']);
    unset($transformation['#collapse']);
    unset($transformation['#lazy_transformer']);
    foreach ($transformation as $key => $value) {
      if (is_array($value)) {
        $this->cleanupCacheMetadata($transformation[$key]);
      }
    }
    if ($collapse) {
      $count = count($transformation);
      if ($count == 1) {
        $transformation = $transformation[array_key_first($transformation)];
      }
    }
  }

  /**
   * Cleanup control keys after transformation.
   *
   * @param array $transformation
   *   JSON array needing to be cleaned.
   */
  protected function cleanup(array &$transformation) {
    foreach ($transformation as $key => $value) {
      if (is_string($key) && $key[0] === '#' && !in_array($key, self::RESERVED_WORDS)) {
        unset($transformation[$key]);
      }
      elseif (is_array($value)) {
        $this->cleanup($transformation[$key]);
      }
    }
  }

  /**
   * Add a caching dependency to a transformation array.
   *
   * @param array $transformation
   *   The target transformation array.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|mixed $dependency
   *   The object that the transformation array depends upon.
   */
  protected function addCacheableDependency(array &$transformation, $dependency) {
    $meta_a = CacheableMetadata::createFromRenderArray($transformation);
    $meta_b = CacheableMetadata::createFromObject($dependency);
    $meta_a->merge($meta_b)->applyTo($transformation);
  }

  /**
   * Handle lazy transformers in a transformation array.
   *
   * @param array $transformation
   *   Transformation array containing lazy transformers.
   */
  protected function handleLazyTransformers(array &$transformation) {
    // First validate the usage of #lazy_builder; both of the next if-statements
    // use it if available.
    if (isset($transformation['#lazy_transformer'])) {
      assert(is_array($transformation['#lazy_transformer']), 'The #lazy_transformer property must have an array as a value.');
      assert(count($transformation['#lazy_transformer']) === 2, 'The #lazy_transformer property must have an array as a value, containing two values: the callback, and the arguments for the callback.');
      assert(count($transformation['#lazy_transformer'][1]) === count(array_filter($transformation['#lazy_transformer'][1], function ($v) {
          return is_null($v) || is_scalar($v);
      })), "A #lazy_transformer callback's context may only contain scalar values or NULL.");
      assert(!Transform::children($transformation), sprintf('When a #lazy_transformer callback is specified, no children can exist; all children must be generated by the #lazy_transformer callback. You specified the following children: %s.', implode(', ', Transform::children($transformation))));
      $supported_keys = self::RESERVED_WORDS;
      assert(empty(array_diff(array_keys($transformation), $supported_keys)), sprintf('When a #lazy_transformer callback is specified, no properties can exist; all properties must be generated by the #lazy_transformer callback. You specified the following properties: %s.', implode(', ', array_diff(array_keys($transformation), $supported_keys))));
    }
    // Build the element if it is still empty.
    if (isset($transformation['#lazy_transformer'])) {
      $new_transformation = $this->doCallback('#lazy_transformer', $transformation['#lazy_transformer'][0], $transformation['#lazy_transformer'][1]);
      // Throw an exception if #lazy_builder callback does not return an array;
      // provide helpful details for troubleshooting.
      assert(is_array($new_transformation), "#lazy_transformer callbacks must return a valid array, got " . gettype($new_transformation) . " from " . Variable::callableToString($transformation['#lazy_transformer'][0]));

      // Retain the original cacheability metadata, plus cache keys.
      CacheableMetadata::createFromRenderArray($transformation)
        ->merge(CacheableMetadata::createFromRenderArray($new_transformation))
        ->applyTo($new_transformation);
      if (isset($transformation['#cache']['keys'])) {
        $new_transformation['#cache']['keys'] = $transformation['#cache']['keys'];
      }
      $transformation = $new_transformation;
    }
  }

  /**
   * Performs a callback.
   *
   * @param string $callback_type
   *   The type of the callback. For example, '#post_render'.
   * @param string|callable $callback
   *   The callback to perform.
   * @param array $args
   *   The arguments to pass to the callback.
   *
   * @return mixed
   *   The callback's return value.
   *
   * @see \Drupal\Core\Security\TrustedCallbackInterface
   */
  protected function doCallback($callback_type, $callback, array $args) {
    if (is_string($callback)) {
      $double_colon = strpos($callback, '::');
      if ($double_colon === FALSE) {
        $callback = $this->controllerResolver->getControllerFromDefinition($callback);
      }
      elseif ($double_colon > 0) {
        $callback = explode('::', $callback, 2);
      }
    }
    $message = sprintf('Transform %s callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was %s.', $callback_type, '%s');
    // Add \Drupal\Core\Render\Element\RenderCallbackInterface as an extra
    // trusted interface so that:
    // - All public methods on Render elements are considered trusted.
    // - Helper classes that contain only callback methods can implement this
    //   instead of TrustedCallbackInterface.
    return $this->doTrustedCallback($callback, $args, $message);
  }

}
