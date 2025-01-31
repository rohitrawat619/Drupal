<?php

namespace Drupal\transform_api\Form;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for the EntityTransformDisplay entity type.
 */
class EntityTransformDisplayEditForm extends EntityDisplayFormBase {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'transform';

  /**
   * The entity display repository.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new EntityDisplayFormBase.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The transform plugin manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   (optional) The entity display_repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   (optional) The entity field manager.
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entity_transform_display_repository
   *   (optional) The transform display_repository.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, PluginManagerBase $plugin_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManagerInterface $entity_field_manager, EntityTransformRepositoryInterface $entity_transform_display_repository) {
    parent::__construct($field_type_manager, $plugin_manager, $entity_display_repository, $entity_field_manager);
    $this->entityDisplayRepository = $entity_transform_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.transform_api.field_transform'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('transform_api.entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_row = parent::buildFieldRow($field_definition, $form, $form_state);

    $field_name = $field_definition->getName();
    $display_options = $this->entity->getComponent($field_name);

    // Insert the label column.
    $label = [
      'label' => [
        '#type' => 'select',
        '#title' => $this->t('Label display for @title', ['@title' => $field_definition->getLabel()]),
        '#title_display' => 'invisible',
        '#options' => $this->getFieldLabelOptions(),
        '#default_value' => $display_options ? $display_options['label'] : 'omit',
      ],
    ];

    $label_position = array_search('plugin', array_keys($field_row));
    $field_row = array_slice($field_row, 0, $label_position, TRUE) + $label + array_slice($field_row, $label_position, count($field_row) - 1, TRUE);

    // Update the (invisible) title of the 'plugin' column.
    $field_row['plugin']['#title'] = $this->t('Formatter for @title', ['@title' => $field_definition->getLabel()]);
    if (!empty($field_row['plugin']['settings_edit_form']) && ($plugin = $this->entity->getRenderer($field_name))) {
      $plugin_type_info = $plugin->getPluginDefinition();
      $field_row['plugin']['settings_edit_form']['label']['#markup'] = $this->t('Format settings:') . ' <span class="plugin-name">' . $plugin_type_info['label'] . '</span>';
    }

    return $field_row;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExtraFieldRow($field_id, $extra_field) {
    $extra_field_row = parent::buildExtraFieldRow($field_id, $extra_field);

    // Insert an empty placeholder for the label column.
    $label = [
      'empty_cell' => [
        '#markup' => '&nbsp;',
      ],
    ];
    $label_position = array_search('plugin', array_keys($extra_field_row));
    return array_slice($extra_field_row, 0, $label_position, TRUE) + $label + array_slice($extra_field_row, $label_position, count($extra_field_row) - 1, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($entity_type_id, $bundle, $mode) {
    return $this->entityDisplayRepository->getTransformDisplay($entity_type_id, $bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultPlugin($field_type) {
    return $this->fieldTypes[$field_type]['default_transform'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModes() {
    return $this->entityDisplayRepository->getTransformModes($this->entity->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModeOptions() {
    return $this->entityDisplayRepository->getTransformModeOptions($this->entity->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayModesLink() {
    return [
      '#type' => 'link',
      '#title' => $this->t('Manage transform modes'),
      '#url' => Url::fromRoute('entity.entity_transform_mode.collection'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableHeader() {
    return [
      $this->t('Field'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Region'),
      $this->t('Label'),
      ['data' => $this->t('Transform'), 'colspan' => 3],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOverviewUrl($mode) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entity->getTargetEntityTypeId());
    return Url::fromRoute('entity.entity_transform_display.' . $this->entity->getTargetEntityTypeId() . '.transform_mode', [
      'transform_mode_name' => $mode,
    ] + FieldUI::getRouteBundleParameter($entity_type, $this->entity->getTargetBundle()));
  }

  /**
   * Returns an array of visibility options for field labels.
   *
   * @return array
   *   An array of visibility options.
   */
  protected function getFieldLabelOptions() {
    return [
      'omit' => $this->t('Omit'),
      'include' => $this->t('Include'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = [];
    // Invoke hook_field_transform_third_party_settings_form(), keying resulting
    // subforms by module name.
    $this->moduleHandler->invokeAllWith(
      'field_transform_third_party_settings_form',
      function (callable $hook, string $module) use (&$settings_form, &$plugin, &$field_definition, &$form, &$form_state) {
        $settings_form[$module] = $hook(
          $plugin,
          $field_definition,
          $this->entity->getMode(),
          $form,
          $form_state,
        );
      }
    );
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSettingsSummary(array &$summary, PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition) {
    $context = [
      'transform' => $plugin,
      'field_definition' => $field_definition,
      'transform_mode' => $this->entity->getMode(),
    ];
    $this->moduleHandler->alter('field_transform_settings_summary', $summary, $context);
  }

}
