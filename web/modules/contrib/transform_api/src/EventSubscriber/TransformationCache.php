<?php

namespace Drupal\transform_api\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RenderCacheInterface;
use Drupal\transform_api\Transform\TransformInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Caches transformations after the response has been sent.
 *
 * @see \Drupal\jsonapi\Normalizer\ResourceObjectNormalizer::getNormalization()
 * @todo Refactor once https://www.drupal.org/node/2551419 lands.
 */
class TransformationCache implements EventSubscriberInterface {

  /**
   * The render cache.
   *
   * @var \Drupal\Core\Render\RenderCacheInterface
   */
  protected $renderCache;

  /**
   * The things to cache after the response has been sent.
   *
   * @var array
   */
  protected $toCache = [];

  /**
   * Sets the render cache service.
   *
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache.
   */
  public function setRenderCache(RenderCacheInterface $render_cache) {
    $this->renderCache = $render_cache;
  }

  /**
   * Reads an entity normalization from cache.
   *
   * The returned normalization may only be a partial transformation because it
   * was previously transformed with a sparse fieldset.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $object
   *   The transform object for which to generate a cache item.
   *
   * @return array|false
   *   The cached transformation, or FALSE if not yet cached.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::renderArrayToResponse()
   */
  public function get(TransformInterface $object) {
    $cached = $this->renderCache->get(static::generateLookupRenderArray($object));
    return $cached ? $cached['#data'] : FALSE;
  }

  /**
   * Adds a transformation to be cached after the response has been sent.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $object
   *   The transform object for which to generate a cache item.
   * @param array $transformation
   *   The transformation to cache.
   */
  public function saveOnTerminate(TransformInterface $object, array $transformation) {
    $key = implode(':', $object->getCacheKeys());
    $this->toCache[$key] = [$object, $transformation];
  }

  /**
   * Writes transformations to cache, if any were created.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   The Event to process.
   */
  public function onTerminate(TerminateEvent $event) {
    foreach ($this->toCache as $value) {
      [$object, $transformation] = $value;
      $this->set($object, $transformation);
    }
  }

  /**
   * Writes a normalization to cache.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $object
   *   The resource object for which to generate a cache item.
   * @param array $transformation
   *   The transformation to cache.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::responseToRenderArray()
   * @todo Refactor/remove once https://www.drupal.org/node/2551419 lands.
   */
  protected function set(TransformInterface $object, array $transformation) {
    $base = static::generateLookupRenderArray($object);
    $data_as_render_array = $base + [
      // The data we actually care about.
      '#data' => $transformation,
      // Tell RenderCache to cache the #data property: the data we actually
      // care about.
      '#cache_properties' => ['#data'],
      // These exist only to fulfill the requirements of the RenderCache,
      // which is designed to work with render arrays only. We don't care
      // about these.
      '#markup' => '',
      '#attached' => '',
    ];

    // Merge the entity's cacheability metadata with that of the normalization
    // parts, so that RenderCache can take care of cache redirects for us.
    CacheableMetadata::createFromObject($object)
      ->merge(CacheableMetadata::createFromRenderArray($transformation))
      ->applyTo($data_as_render_array);

    $this->renderCache->set($data_as_render_array, $base);
  }

  /**
   * Generates a lookup render array for a normalization.
   *
   * @param \Drupal\transform_api\Transform\TransformInterface $object
   *   The resource object for which to generate a cache item.
   *
   * @return array
   *   A render array for use with the RenderCache service.
   *
   * @see \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber::$dynamicPageCacheRedirectRenderArray
   */
  protected static function generateLookupRenderArray(TransformInterface $object) {
    return [
      '#cache' => [
        'keys' => $object->getCacheKeys(),
        'bin' => 'transform',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['onTerminate'];
    return $events;
  }

}
