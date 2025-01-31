<?php

namespace Drupal\transform_api\Plugin\Transform\Block;

use Drupal\Core\Block\BlockPluginTrait;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\transform_api\TransformBlockPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a fallback plugin for missing transform block plugins.
 *
 * @TransformBlock(
 *   id = "broken",
 *   admin_label = @Translation("Broken/Missing"),
 *   category = @Translation("Block"),
 * )
 */
class Broken extends PluginBase implements TransformBlockPluginInterface {

  use BlockPluginTrait;
  use CacheableDependencyTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a Broken Block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    $transformation = [];
    if ($this->currentUser->hasPermission('administer blocks')) {
      $transformation += $this->brokenMessage();
    }
    return $transformation;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $this->brokenMessage();
  }

  /**
   * Generate message with debugging information as to why the block is broken.
   *
   * @return array
   *   Render array containing debug information.
   */
  protected function brokenMessage() {
    $build['message'] = [
      '#markup' => $this->t('This block is broken or missing. You may be missing content or you might need to enable the original module.'),
    ];

    return $build;
  }

}
