<?php

namespace Drupal\transform_api\Plugin\Transform\Type;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\transform_api\Transform\BlockTransform;
use Drupal\transform_api\Transform\TransformInterface;
use Drupal\transform_api\TransformationTypeBase;
use Drupal\transform_api\TransformBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin for request path transform types.
 *
 * @TransformationType(
 *  id = "request_path",
 *  title = "Request Path"
 * )
 */
class RequestPath extends TransformationTypeBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The transform blocks service.
   *
   * @var \Drupal\transform_api\TransformBlocks
   */
  private TransformBlocks $transformBlocks;

  /**
   * Construct a request path transform type plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\transform_api\TransformBlocks $transformBlocks
   *   The transform blocks service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransformBlocks $transformBlocks, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
    $this->transformBlocks = $transformBlocks;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('transform_api.transform_blocks'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function transform(TransformInterface $transform) {
    $region = $transform->getValue('region');
    $cacheMetadata = new CacheableMetadata();
    $cacheMetadata->addCacheTags(['config:transform_api.regions']);
    $cacheable_metadata_list = [];
    $transformation = [];

    if (empty($region)) {
      foreach ($this->transformBlocks->getVisibleBlocksPerRegion($cacheable_metadata_list) as $region => $blocks) {
        $transformation[$region]['#collapse'] = $this->transformBlocks->shouldRegionsCollapse();
        foreach ($blocks as $block) {
          $transformation[$region][$block->id()] = BlockTransform::createFromBlock($block);
        }
      }
      foreach ($cacheable_metadata_list as $cacheable_metadata) {
        $cacheMetadata = $cacheMetadata->merge($cacheable_metadata);
      }
    }
    else {
      $transformation['#collapse'] = $this->transformBlocks->shouldRegionsCollapse();
      foreach ($this->transformBlocks->getVisibleBlocksPerRegion($cacheable_metadata_list)[$region] as $block) {
        $transformation[$block->id()] = BlockTransform::createFromBlock($block);
      }
      $cacheMetadata = $cacheMetadata->merge($cacheable_metadata_list[$region]);
    }

    $cacheMetadata->applyTo($transformation);
    return $transformation;
  }

}
