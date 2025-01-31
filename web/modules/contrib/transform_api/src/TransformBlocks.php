<?php

namespace Drupal\transform_api;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Transform blocks service.
 */
class TransformBlocks {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * The region config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * The cache backend for caching the block tree.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private CacheBackendInterface $cacheBackend;

  /**
   * The storage service for transform blocks.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $blockStorage;

  /**
   * Construct the transform blocks service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory for fetching the region config.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend for caching the block tree.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The storage service for transform blocks.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $configFactory, CacheBackendInterface $cacheBackend, EntityTypeManagerInterface $entityTypeManager) {
    $this->moduleHandler = $moduleHandler;
    $this->config = $configFactory->get('transform_api.regions');
    $this->cacheBackend = $cacheBackend;
    $this->blockStorage = $entityTypeManager->getStorage('transform_block');
  }

  /**
   * Get list of all regions.
   *
   * @return array
   *   Array with all regions.
   */
  public function getRegions(): array {
    return $this->config->get('regions') ?? [];
  }

  /**
   * Get the default region.
   *
   * @return string
   *   The default region.
   */
  public function getDefaultRegion() {
    $default = $this->config->get('default_region') ?? '';
    $regions = $this->getRegions();
    if (empty($default) || !isset($regions[$default])) {
      if (!empty($regions)) {
        $default = array_key_first($regions);
      }
    }
    return $default;
  }

  /**
   * Get all active transform blocks.
   *
   * @return array
   *   Array of transform blocks indexed by region.
   */
  public function getBlocks(): array {
    $blocks = [];
    $entities = $this->blockStorage->loadMultiple();

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, ['\Drupal\transform_api\Entity\TransformBlock', 'sort']);

    /** @var \Drupal\transform_api\TransformBlockInterface $entity */
    foreach ($entities as $entity) {
      $blocks[$entity->getRegion()][] = $entity->id();
    }

    return $blocks;
  }

  /**
   * Get all visible transform blocks per region.
   *
   * @param array $cacheable_metadata
   *   Collected caching metadata for the transform blocks.
   *
   * @return array
   *   Array of transform blocks indexed by region.
   */
  public function getVisibleBlocksPerRegion(array &$cacheable_metadata = []) {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegions()), []);

    $full = [];
    foreach ($this->blockStorage->loadMultiple() as $block_id => $block) {
      /** @var \Drupal\transform_api\TransformBlockInterface $block */
      $access = $block->access('view', NULL, TRUE);
      $region = $block->getRegion();
      if (!isset($cacheable_metadata[$region])) {
        $cacheable_metadata[$region] = CacheableMetadata::createFromObject($access);
      }
      else {
        $cacheable_metadata[$region] = $cacheable_metadata[$region]->merge(CacheableMetadata::createFromObject($access));
      }

      // Set the contexts on the block before checking access.
      if ($access->isAllowed()) {
        $full[$region][$block_id] = $block;
      }
    }

    // Merge it with the actual values to maintain the region ordering.
    $assignments = array_intersect_key(array_merge($empty, $full), $empty);
    foreach ($assignments as &$assignment) {
      // Suppress errors because PHPUnit will indirectly modify the contents,
      // triggering https://bugs.php.net/bug.php?id=50688.
      @uasort($assignment, 'Drupal\transform_api\Entity\TransformBlock::sort');
    }
    return $assignments;
  }

  /**
   * Answer whether empty regions should be collapsed.
   *
   * @return bool
   *   Whether empty regions should be collapsed
   */
  public function shouldRegionsCollapse() {
    return (bool) $this->config->get('collapse_regions');
  }

  /**
   * Return whether a region exists.
   *
   * @param string $id
   *   The region in question.
   *
   * @return bool
   *   Whether a region exists.
   */
  public static function regionExists($id) {
    /** @var \Drupal\transform_api\TransformBlocks $transformBlocks */
    $transformBlocks = \Drupal::service('transform_api.transform_blocks');
    return isset($transformBlocks->getRegions()[$id]);
  }

}
