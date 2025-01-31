<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\transform_api\FieldTransformBase;
use Drupal\transform_api\SectionComponentTransformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform field plugin for layout builder.
 *
 * @FieldTransform(
 *  id = "layout_builder",
 *  label = @Translation("Layout Builder"),
 *  field_types = {
 *    "layout_section"
 *  }
 * )
 */
class LayoutBuilderTransform extends FieldTransformBase {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  private LayoutPluginManagerInterface $layoutPluginManager;

  /**
   * The section component transform manager.
   *
   * @var \Drupal\transform_api\SectionComponentTransformManager
   */
  private SectionComponentTransformManager $componentTransformManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  private ContextRepositoryInterface $contextRepository;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  private SectionStorageManagerInterface $sectionStorageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a LayoutBuilderTransform object.
   *
   * @param string $plugin_id
   *   The plugin_id for the transform.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the transform is associated.
   * @param array $settings
   *   The transform settings.
   * @param string $label
   *   The transform label display setting.
   * @param string $transform_mode
   *   The transform mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layoutPluginManager
   *   The layout plugin manager.
   * @param \Drupal\transform_api\SectionComponentTransformManager $componentTransformManager
   *   The section component transform manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager
   *   The section storage manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings, LayoutPluginManagerInterface $layoutPluginManager, SectionComponentTransformManager $componentTransformManager, ContextRepositoryInterface $contextRepository, SectionStorageManagerInterface $sectionStorageManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $transform_mode, $third_party_settings);
    $this->layoutPluginManager = $layoutPluginManager;
    $this->componentTransformManager = $componentTransformManager;
    $this->contextRepository = $contextRepository;
    $this->sectionStorageManager = $sectionStorageManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['transform_mode'], $configuration['third_party_settings'],
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin.manager.transform_api.section_component_transform'),
      $container->get('context.repository'),
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode): array {
    $entity = $items->getEntity();
    $contexts = $this->getContextsForEntity($entity);
    // @todo Remove in https://www.drupal.org/project/drupal/issues/3018782.
    $label = new TranslatableMarkup('@entity being viewed', [
      '@entity' => $entity->getEntityType()->getSingularLabel(),
    ]);
    $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity, $label);

    $cacheability = new CacheableMetadata();
    $storage = $this->sectionStorageManager->findByContext($contexts, $cacheability);

    $build = [];
    if ($storage) {
      foreach ($storage->getSections() as $delta => $section) {
        $build[$delta] = $this->transformSection($section, $contexts, $label);
      }
    }
    // The render array is built based on decisions made by @SectionStorage
    // plugins, and therefore it needs to depend on the accumulated
    // cacheability of those decisions.
    $cacheability->applyTo($build);
    return $build;
  }

  /**
   * Take a section object and transform it.
   *
   * @param \Drupal\layout_builder\Section $section
   *   The section object to transform.
   * @param array $contexts
   *   Array of contexts.
   * @param string $label
   *   Label for the section.
   *
   * @return array
   *   The JSON array.
   */
  public function transformSection(Section $section, array $contexts, $label) {
    $transform_mode = $section->getLayoutId();

    $transformation = array_merge(['type' => 'section'], $section->toArray());
    unset($transformation['components']);
    $regions = [];
    $transformation['regions'] = [];
    try {
      $definition = $this->layoutPluginManager->getDefinition($section->getLayoutId());
      foreach (array_keys($definition->getRegions()) as $region) {
        $transformation['regions'][$region] = [];
        $regions[$region] = [];
      }
    }
    catch (PluginNotFoundException) {
    }

    foreach ($section->getComponents() as $component) {
      $regions[$component->getRegion()][$component->getWeight()] = $component;
    }
    foreach ($regions as $region => $components) {
      ksort($components);
      foreach ($components as $component) {
        $componentPlugin = $component->getPlugin();
        $configuration = ['contexts' => $contexts];
        $transformPlugin = $this->componentTransformManager->createInstance($componentPlugin->getPluginDefinition()['id'], $configuration);
        $transformation['regions'][$region][] = $transformPlugin->transform($component, $transform_mode);
      }
    }
    $hooks = ['transform', 'section_transform'];
    $this->moduleHandler->alter($hooks, $transformation);

    return $transformation;
  }

  /**
   * Gets the available contexts for a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of context objects for a given entity.
   */
  protected function getContextsForEntity(FieldableEntityInterface $entity) {
    $available_context_ids = array_keys($this->contextRepository->getAvailableContexts());
    $display = EntityViewDisplay::load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $this->getTransformMode());
    if (empty($display)) {
      $display = EntityViewDisplay::load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default');
    }
    return [
      'view_mode' => new Context(ContextDefinition::create('string'), $this->getTransformMode()),
      'entity' => EntityContext::fromEntity($entity),
      'display' => EntityContext::fromEntity($display),
    ] + $this->contextRepository->getRuntimeContexts($available_context_ids);
  }

}
