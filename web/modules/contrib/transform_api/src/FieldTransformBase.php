<?php

namespace Drupal\transform_api;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for field transform plugins.
 */
abstract class FieldTransformBase extends PluginSettingsBase implements FieldTransformInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The transform settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The transform mode.
   *
   * @var string
   */
  protected $transformMode;

  /**
   * Constructs a FieldTransformBase object.
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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings) {
    parent::__construct([], $plugin_id, $plugin_definition);

    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->label = $label;
    $this->transformMode = $transform_mode;
    $this->thirdPartySettings = $third_party_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['transform_mode'], $configuration['third_party_settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareTransform(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function transform(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $elements = $this->transformElements($items, $langcode);

    if (Transform::isTransform($elements) || !Transform::children($elements)) {
      if (is_array($elements)) {
        if (!$this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
          $elements['value'] = NULL;
        }
      }
      elseif (empty($elements)) {
        $elements = [
          '#collapse' => TRUE,
          'value' => NULL,
        ];
      }
    }

    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $field_name = $this->fieldDefinition->getName();
    $info = [
      '#transform' => 'field',
      '#title' => $this->fieldDefinition->getLabel(),
      '#transform_mode' => $this->transformMode,
      '#language' => $items->getLangcode(),
      '#field_name' => $field_name,
      '#field_type' => $this->fieldDefinition->getType(),
      '#field_translatable' => $this->fieldDefinition->isTranslatable(),
      '#entity_type' => $entity_type,
      '#bundle' => $entity->bundle(),
      '#object' => $entity,
      '#items' => $items,
      '#transformer' => $this->getPluginId(),
      '#is_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
      '#third_party_settings' => $this->getThirdPartySettings(),
      '#collapse' => !$this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
    ];
    $transformation = array_merge($info, $elements);

    if ($this->fieldDefinition->isTranslatable()) {
      $cacheMetadata = CacheableMetadata::createFromRenderArray($transformation);
      $cacheMetadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
      $cacheMetadata->applyTo($transformation);
    }

    return $transformation;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformMode() {
    return $this->transformMode;
  }

  /**
   * Returns the array of field settings.
   *
   * @return array
   *   The array of settings.
   */
  protected function getFieldSettings() {
    return $this->fieldDefinition->getSettings();
  }

  /**
   * Returns the value of a field setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getFieldSetting($setting_name) {
    return $this->fieldDefinition->getSetting($setting_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // By default, formatters are available for all fields.
    return TRUE;
  }

}
