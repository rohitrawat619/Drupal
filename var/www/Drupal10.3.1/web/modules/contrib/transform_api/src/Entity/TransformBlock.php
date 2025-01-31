<?php

namespace Drupal\transform_api\Entity;

use Drupal\block\BlockPluginCollection;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\transform_api\TransformBlockInterface;

/**
 * Defines a Transform Block configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "transform_block",
 *   label = @Translation("Transform block"),
 *   label_collection = @Translation("Transform blocks"),
 *   label_singular = @Translation("transform block"),
 *   label_plural = @Translation("transform blocks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transform block",
 *     plural = "@count transform blocks",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\transform_api\Access\TransformBlockAccessControlHandler",
 *     "list_builder" = "Drupal\transform_api\TransformBlockListBuilder",
 *     "form" = {
 *       "default" = "Drupal\transform_api\Form\TransformBlockForm",
 *       "delete" = "Drupal\transform_api\Form\TransformBlockDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer transform blocks",
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   links = {
 *     "collection" = "/admin/config/block/transform",
 *     "delete-form" = "/admin/structure/block/transform/manage/{transform_block}/delete",
 *     "edit-form" = "/admin/structure/block/transform/manage/{transform_block}",
 *     "enable" = "/admin/structure/block/transform/manage/{transform_block}/enable",
 *     "disable" = "/admin/structure/block/transform/manage/{transform_block}/disable",
 *   },
 *   config_export = {
 *     "id",
 *     "region",
 *     "weight",
 *     "provider",
 *     "plugin",
 *     "settings",
 *     "visibility",
 *   },
 *   lookup_keys = {
 *     "region"
 *   }
 * )
 */
class TransformBlock extends ConfigEntityBase implements TransformBlockInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of the block.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The region this block is placed in.
   *
   * @var string
   */
  protected $region;

  /**
   * The block weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The visibility settings for this block.
   *
   * @var array
   */
  protected $visibility = [];

  /**
   * The plugin collection that holds the block plugin for this entity.
   *
   * @var \Drupal\block\BlockPluginCollection
   */
  protected $pluginCollection;

  /**
   * The available contexts for this block and its visibility conditions.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * The visibility collection.
   *
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $visibilityCollection;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * Encapsulates the creation of the block's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The block's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new BlockPluginCollection(\Drupal::service('plugin.manager.transform_api.transform_block'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
      'visibility' => $this->getVisibilityConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $settings = $this->get('settings');
    if ($settings['label']) {
      return $settings['label'];
    }
    else {
      $definition = $this->getPlugin()->getPluginDefinition();
      return $definition['admin_label'];
    }
  }

  /**
   * Sorts active blocks by weight; sorts inactive blocks by name.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    // Separate enabled from disabled.
    $status = (int) $b->status() - (int) $a->status();
    if ($status !== 0) {
      return $status;
    }

    // Sort by weight.
    $weight = $a->getWeight() - $b->getWeight();
    if ($weight) {
      return $weight;
    }

    // Sort by label.
    return strcmp($a->label(), $b->label());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The Block entity has one special case: a
    // newly created block may *also* appear on any page in the current theme,
    // so we must invalidate the associated block's cache tag (which includes
    // the theme cache tag).
    if (!$update) {
      Cache::invalidateTags($this->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->getVisibilityConditions()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibilityConfig($instance_id, array $configuration) {
    $conditions = $this->getVisibilityConditions();
    if (!$conditions->has($instance_id)) {
      $configuration['id'] = $instance_id;
      $conditions->addInstanceId($instance_id, $configuration);
    }
    else {
      $conditions->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions() {
    if (!isset($this->visibilityCollection)) {
      $this->visibilityCollection = new ConditionPluginCollection($this->conditionPluginManager(), $this->get('visibility'));
    }
    return $this->visibilityCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityCondition($instance_id) {
    return $this->getVisibilityConditions()->get($instance_id);
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion($region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicateBlock($new_id = NULL) {
    $duplicate = parent::createDuplicate();
    if (!empty($new_id)) {
      $duplicate->id = $new_id;
    }
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    /** @var \Drupal\transform_api\TransformBlocks $transform_blocks */
    $transform_blocks = \Drupal::service('transform_api.transform_blocks');
    // Ensure the region is valid to mirror the behavior of block_rebuild().
    // This is done primarily for backwards compatibility support of
    // \Drupal\block\BlockInterface::BLOCK_REGION_NONE.
    $regions = $transform_blocks->getRegions();
    if (!isset($regions[$this->region]) && $this->status()) {
      $this
        ->setRegion($transform_blocks->getDefaultRegion())
        ->disable();
    }
  }

}
