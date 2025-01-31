<?php

namespace Drupal\transform_api\Plugin\Transform\Type;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\transform_api\Transform\TransformInterface;
use Drupal\transform_api\TransformationTypeBase;
use Drupal\transform_api\TransformBlockManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for block transform types.
 *
 * @TransformationType(
 *  id = "block",
 *  title = "Block transform"
 * )
 */
class Block extends TransformationTypeBase {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The transform block manager.
   *
   * @var \Drupal\transform_api\TransformBlockManager
   */
  protected TransformBlockManager $blockTransformManager;

  /**
   * Construct a block transform type plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\transform_api\TransformBlockManager $blockTransformManager
   *   The transform block manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, TransformBlockManager $blockTransformManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->blockTransformManager = $blockTransformManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('plugin.manager.transform_api.transform_block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform(TransformInterface $transform) {
    /** @var \Drupal\transform_api\Transform\BlockTransform $block_transform */
    $block_transform = $transform;
    $config = $block_transform->getBlock();
    $plugin = $config->getPlugin();
    $cache = CacheableMetadata::createFromObject($plugin);
    $cache->addCacheableDependency($config);
    $transformation = array_merge(['#block_id' => $transform->getValue('id')], $plugin->transform());
    $cache->addCacheableDependency(CacheableMetadata::createFromRenderArray($transformation));
    $cache->applyTo($transformation);
    return $transformation;
  }

}
