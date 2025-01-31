<?php

namespace Drupal\transform_api\Plugin\Transform\SectionComponent;

use Drupal\layout_builder\Plugin\Block\FieldBlock as LayoutFieldBlock;
use Drupal\layout_builder\SectionComponent;
use Drupal\transform_api\Entity\EntityTransformDisplay;
use Drupal\transform_api\FieldTransformManager;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\SectionComponentTransformBase;
use Drupal\transform_api\Transform\FieldTransform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Section component plugin for field blocks.
 *
 * @SectionComponentTransform(
 *  id = "field_block",
 *  title = "Field  Block"
 * )
 */
class FieldBlock extends SectionComponentTransformBase {

  /**
   * The field transform manager.
   *
   * @var \Drupal\transform_api\FieldTransformManager
   */
  protected $fieldTransformManager;

  /**
   * Construct FieldBlock plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\transform_api\FieldTransformManager $fieldTransformManager
   *   The field transform manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FieldTransformManager $fieldTransformManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldTransformManager = $fieldTransformManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.manager.transform_api.field_transform'));
  }

  /**
   * {@inheritdoc}
   */
  public function transform(SectionComponent $component, $transform_mode = EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE) {
    /** @var \Drupal\layout_builder\Plugin\Block\FieldBlock $plugin */
    $plugin = $component->getPlugin($this->configuration['contexts']);
    // Get the entity type and field name from the plugin ID.
    [, , , $field_name] = explode(static::DERIVATIVE_SEPARATOR, $plugin->getPluginId(), 4);
    $entity = $this->getEntity($plugin);
    $display = EntityTransformDisplay::collectTransformDisplay($entity, $transform_mode);
    $definition = $display->getComponent($field_name);
    if (!empty($definition)) {
      $configuration = [
        'field_definition' => $entity->get($field_name)->getFieldDefinition(),
        'settings' => $definition['settings'] ?? [],
        'label' => $definition['label'] ?? '',
        'transform_mode' => $transform_mode,
        'third_party_settings' => $definition['third_party_settings'] ?? [],
      ];
      $fieldPlugin = $this->fieldTransformManager->createInstance($definition['type'], $configuration);
      $fieldPlugin->prepareTransform([$entity->id() => $entity->get($field_name)]);
      return new FieldTransform($fieldPlugin, $entity, $field_name);
    }
    else {
      return NULL;
    }
  }

  /**
   * Gets the entity that has the field.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  protected function getEntity(LayoutFieldBlock $plugin) {
    return $plugin->getContextValue('entity');
  }

}
