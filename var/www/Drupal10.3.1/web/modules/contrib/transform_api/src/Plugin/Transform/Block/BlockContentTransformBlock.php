<?php

namespace Drupal\transform_api\Plugin\Transform\Block;

use Drupal\block_content\BlockContentUuidLookup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\TransformBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Main page content' block.
 *
 * @TransformBlock(
 *   id = "block_content",
 *   admin_label = @Translation("Content block"),
 *   category = @Translation("Content block"),
 *   deriver = "Drupal\transform_api\Plugin\Derivative\BlockContentTransformBlock",
 * )
 */
class BlockContentTransformBlock extends TransformBlockBase {

  /**
   * The Plugin Block Manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The block content entity.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockContent;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The block content UUID lookup service.
   *
   * @var \Drupal\block_content\BlockContentUuidLookup
   */
  protected $uuidLookup;

  /**
   * The entity transform repository.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected $entityTransformRepository;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The Plugin Block Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which view access should be checked.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\block_content\BlockContentUuidLookup $uuid_lookup
   *   The block content UUID lookup service.
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entity_transform_repository
   *   The entity transform repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlockManagerInterface $block_manager, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, UrlGeneratorInterface $url_generator, BlockContentUuidLookup $uuid_lookup, EntityTransformRepositoryInterface $entity_transform_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->blockManager = $block_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->urlGenerator = $url_generator;
    $this->uuidLookup = $uuid_lookup;
    $this->entityTransformRepository = $entity_transform_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('block_content.uuid_lookup'),
      $container->get('transform_api.entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'status' => TRUE,
      'info' => '',
      'transform_mode' => 'full',
    ];
  }

  /**
   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
   *
   * Adds body and description fields to the block configuration form.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block = $this->getEntity();
    if (!$block) {
      return $form;
    }
    $options = $this->entityTransformRepository->getTransformModeOptionsByBundle('block_content', $block->bundle());

    $form['transform_mode'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Transform mode'),
      '#description' => $this->t('Transform the Content Block in this mode.'),
      '#default_value' => $this->configuration['transform_mode'],
    ];
    $form['title']['#description'] = $this->t('The title of the block as shown to the user.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Invalidate the block cache to update content block-based derivatives.
    $this->configuration['transform_mode'] = $form_state->getValue('transform_mode');
    $this->blockManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->getEntity()) {
      return $this->getEntity()->access('view', $account, TRUE);
    }
    return AccessResult::forbidden();
  }



  public function transform() {
    $block = $this->getEntity();
    if (!$block) {
      return [];
    }

    $transform_mode = $this->configuration['transform_mode'];

    return EntityTransform::createFromEntity($block, $transform_mode)->transform();
  }

  /**
   * Loads the block content entity of the block.
   *
   * @return \Drupal\block_content\BlockContentInterface|null
   *   The block content entity.
   */
  protected function getEntity() {
    if (!isset($this->blockContent)) {
      $uuid = $this->getDerivativeId();
      if ($id = $this->uuidLookup->get($uuid)) {
        $this->blockContent = $this->entityTypeManager->getStorage('block_content')->load($id);
      }
    }
    return $this->blockContent;
  }

  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $block = $this->getEntity();

    if ($block) {
      $cache_tags = Cache::mergeTags($cache_tags, $block->getCacheTags());
    }

    return $cache_tags;
  }

  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $block = $this->getEntity();

    if ($block) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, $block->getCacheContexts());
    }

    return $cache_contexts;
  }

}
