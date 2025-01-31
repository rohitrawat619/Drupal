<?php

namespace Drupal\transform_api_responsive_image\Plugin\Transform\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\transform_api\FieldFormatterWrapper;
use Drupal\transform_api\FieldTransformInterface;
use Drupal\transform_api\Transform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for responsive image transformer.
 *
 * @FieldTransform(
 *  id = "responsive_image",
 *  label = @Translation("Responsive Image Style"),
 *  description = "Output an image using an image style",
 *  field_types = {
 *    "image"
 *  }
 * )
 */
class ResponsiveImageTransform extends ResponsiveImageFormatter implements FieldTransformInterface {

  use FieldFormatterWrapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['transform_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as $delta => $variables) {
      $cache = CacheableMetadata::createFromRenderArray($variables);
      $item = $variables['#item'];
      Transform::renderArrayToTransform($variables);
      $variables['item'] = $item;
      template_preprocess_responsive_image_formatter($variables);
      Transform::renderArrayToTransform($variables);
      Transform::renderArrayToTransform($variables['responsive_image']);
      template_preprocess_responsive_image($variables['responsive_image']);
      if (\Drupal::moduleHandler()->moduleExists('webp')) {
        webp_preprocess_responsive_image($variables['responsive_image']);
      }
      /** @var \Drupal\Core\Template\Attribute $attributes */
      foreach ($variables['responsive_image']['sources'] ?? [] as $key => $attributes) {
        $variables['responsive_image']['sources'][$key] = $attributes->toArray();
        $variables['responsive_image']['sources'][$key]['srcset'] = $this->makeSrcsetAbsolute($variables['responsive_image']['sources'][$key]['srcset']);
      }
      Transform::renderArrayToTransform($variables['responsive_image']['img_element']);
      $url = $this->pathToUrl($variables['responsive_image']['img_element']['uri']);
      $variables['responsive_image']['img_element']['uri'] = $url->toString();

      $transform = $variables['responsive_image'];
      unset($transform['uri']);
      $transform['url'] = $variables['url'];
      $cache->applyTo($transform);

      $elements[$delta] = $transform;
    }
    return $elements;
  }

  /**
   * Taking a srcset and make it's url absolute.
   *
   * @param string $srcset
   *   A srcset string.
   *
   * @return string
   *   A srcset string with absolute urls.
   */
  protected function makeSrcsetAbsolute($srcset) {
    $result = [];
    $sources = explode(',', $srcset);
    foreach ($sources as $source) {
      $parts = explode(" ", trim($source));
      $url = $this->pathToUrl($parts[0]);
      $result[] = $url->toString() . ' ' . $parts[1];
    }
    return implode(', ', $result);
  }

  /**
   * Taking a path and return it as an absolute Url object.
   *
   * @param string $path
   *   An url path as a string.
   *
   * @return \Drupal\Core\Url
   *   Absolute Url object.
   */
  protected function pathToUrl($path) {
    $uri_parts = parse_url($path);
    $query_parts = explode("=", $uri_parts['query']);
    parse_str($uri_parts['query'], $query_parts);
    $url = Url::fromUri('base:' . rawurldecode($uri_parts['path']), ['query' => $query_parts]);
    $url->setAbsolute(TRUE);
    return $url;
  }

}
