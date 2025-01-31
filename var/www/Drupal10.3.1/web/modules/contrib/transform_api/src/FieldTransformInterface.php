<?php

namespace Drupal\transform_api;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface definition for field transformer plugins.
 *
 * @ingroup field_transformer
 */
interface FieldTransformInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ContainerFactoryPluginInterface, PluginSettingsInterface {

  /**
   * Returns the transform mode the plugin is configured for.
   *
   * @return string
   *   The transform mode.
   */
  public function getTransformMode();

  /**
   * Allows transformers to load information for field values being transformed.
   *
   * This should be used when a transformer needs to load additional information
   * from the database in order to transform a field, for example a reference
   * field that transform properties of the referenced entities such as name or
   * type.
   *
   * This method operates on multiple entities. The $entities_items parameter
   * is an array keyed by entity ID. For performance reasons, information for
   * all involved entities should be loaded in a single query where possible.
   *
   * Changes or additions to field values are done by directly altering the
   * items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface[] $entities_items
   *   An array with the field values from the multiple entities being
   *   transformed.
   */
  public function prepareTransform(array $entities_items);

  /**
   * Builds a transform array for a fully transformed field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be transformed.
   * @param string $langcode
   *   (optional) The language that should be used to transform the field.
   *   Defaults to the current content language.
   *
   * @return array
   *   A transform array for a transformed field with its label and all its
   *   values.
   */
  public function transform(FieldItemListInterface $items, $langcode = NULL);

  /**
   * Builds a transform array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be transformed.
   * @param string $langcode
   *   The language that should be used to transform the field.
   *
   * @return array
   *   A transform array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function transformElements(FieldItemListInterface $items, $langcode);

  /**
   * Return form for editing field settings.
   *
   * @param array $form
   *   The form the settings will be inserted into.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   Form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Return summary of the field settings.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The summary of the field settings.
   */
  public function settingsSummary();

  /**
   * Returns if the transformer can be used for the provided field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition that should be checked.
   *
   * @return bool
   *   TRUE if the transformer can be used, FALSE otherwise.
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition);

}
