<?php

namespace Drupal\transform_api\Plugin\Transform\Field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transform_api\FieldTransformBase;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform as TransformEntityTransform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform field plugin for entity reference field types.
 *
 * @FieldTransform(
 *  id = "entity_transform",
 *  label = @Translation("Entity transform"),
 *  field_types = {
 *    "entity_reference",
 *    "entity_reference_revisions"
 *  }
 * )
 */
class EntityTransform extends FieldTransformBase {

  /**
   * The transform mode repository.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected $entityTransformRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityTransform object.
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
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entityTransformRepository
   *   The transform mode repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings, EntityTransformRepositoryInterface $entityTransformRepository, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $transform_mode, $third_party_settings);
    $this->entityTransformRepository = $entityTransformRepository;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['transform_mode'], $configuration['third_party_settings'], $container->get('transform_api.entity_display.repository'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'transform_mode' => EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'transform_mode' => [
        '#type' => 'select',
        '#title' => $this->t('Transform mode'),
        '#options' => $this->getTransformModes(),
        '#default_value' => $this->getSetting('transform_mode'),
        '#required' => TRUE,
      ],

        // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * Returns list of available transform modes.
   *
   * @return array
   *   List of transform modes
   */
  public function getTransformModes(): array {
    $transform_modes = [];
    $entity_type_id = $this->getFieldSetting('target_type');
    foreach ($this->getFieldSetting('handler_settings')['target_bundles'] ?? [] as $bundle) {
      $transform_modes += $this->entityTransformRepository->getTransformModeOptionsByBundle($entity_type_id, $bundle);
    }
    if (empty($transform_modes)) {
      $transform_modes = [EntityTransformRepositoryInterface::DEFAULT_DISPLAY_MODE => $this->t('Default')];
    }
    return $transform_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Transform mode: @transform_mode', [
      '@transform_mode' => $this->getTransformModes()[$this->getSetting('transform_mode')],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity_type_id = $items->getSetting('target_type');
    $transform_mode = $this->getSetting('transform_mode');
    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
      $ids = [];
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($items as $item) {
        if (!empty($item->getValue()['target_id'])) {
          $ids[] = $item->getValue()['target_id'];
        }
      }
      $elements = new TransformEntityTransform($entity_type_id, $ids, $transform_mode, $langcode);
      $elements = $elements->transform();
    }
    else {
      foreach ($items as $item) {
        if (!empty($item->getValue()['target_id'])) {
          $elements[] = new TransformEntityTransform($entity_type_id, $item->getValue()['target_id'], $transform_mode, $langcode);
        }
      }
    }
    return $elements;
  }

}
