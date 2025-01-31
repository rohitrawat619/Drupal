<?php

namespace Drupal\transform_api_language\Plugin\Transform\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\transform_api\TransformBlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Language switcher' block.
 *
 * @TransformBlock(
 *   id = "language_block",
 *   admin_label = @Translation("Language switcher"),
 *   category = @Translation("System"),
 *   deriver = "Drupal\transform_api_language\Plugin\Derivative\LanguageTransformBlock"
 * )
 */
class LanguageTransformBlock extends TransformBlockBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    $transformation = [];
    $type = $this->getDerivativeId();
    $route_match = \Drupal::routeMatch();
    // If there is no route match, for example when creating blocks on 404 pages
    // for logged-in users with big_pipe enabled using the front page instead.
    $url = $route_match->getRouteObject() ? Url::fromRouteMatch($route_match) : Url::fromRoute('<front>');
    $links = $this->languageManager->getLanguageSwitchLinks($type, $url);

    if (isset($links->links)) {
      $transformation = [
        'method_id' => $links->method_id,
        'links' => $links->links,
      ];
      foreach ($links->links as $langcode => $link) {
        $query = $link['query'];
        unset($query['format']);
        unset($query['region']);
        /** @var \Drupal\Core\Url $url */
        $url = $link['url'];
        $url->setOption('query', $query);
        $transformation['links'][$langcode] = [
          'title' => $link['title'],
          'url' => $url->toString(),
        ];
      }
    }
    return $transformation;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2232375.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
