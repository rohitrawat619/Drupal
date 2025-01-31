<?php

namespace Drupal\transform_api_language\Plugin\Transform\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\transform_api\FieldTransformBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform field plugin for language field types.
 *
 * @FieldTransform(
 *  id = "language_name",
 *  label = @Translation("Language name"),
 *  field_types = {
 *    "language"
 *  }
 * )
 */
class LanguageNameTransform extends FieldTransformBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a LanguageNameTransform instance.
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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $transform_mode, $third_party_settings);

    $this->languageManager = $language_manager;
  }

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
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['native_language'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['native_language'] = [
      '#title' => $this->t('Display in native language'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('native_language'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('native_language')) {
      $summary[] = $this->t('Displayed in native language');
    }
    return $summary;
  }

  /**
   * {@inheritDoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $item) {
      // The 'languages' cache context is not necessary because the language is
      // either displayed in its configured form (loaded directly from config
      // storage by LanguageManager::getLanguages()) or in its native language
      // name. That only depends on transform settings and no language
      // condition.
      $languages = $this->getSetting('native_language') ? $this->languageManager->getNativeLanguages(LanguageInterface::STATE_ALL) : $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
      $values[] = $item->language && isset($languages[$item->language->getId()]) ? $languages[$item->language->getId()]->getName() : '';
    }
    return $values;
  }

}
