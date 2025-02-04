<?php

namespace Drupal\transform_api\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides transform block plugin definitions for content blocks.
 *
 * @see \Drupal\block_content\Plugin\Derivative\BlockContent
 */
class BlockContentTransformBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The content block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The content block storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->blockContentStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_contents = $this->blockContentStorage->loadByProperties(['reusable' => TRUE]);
    // Reset the discovered definitions.
    $this->derivatives = [];
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    foreach ($block_contents as $block_content) {
      $this->derivatives[$block_content->uuid()] = $base_plugin_definition;
      $this->derivatives[$block_content->uuid()]['admin_label'] = $block_content->label() ?? ($block_content->getEntityType()->getLabel() . ': ' . $block_content->id());
      $this->derivatives[$block_content->uuid()]['config_dependencies']['content'] = [
        $block_content->getConfigDependencyName(),
      ];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }


}
