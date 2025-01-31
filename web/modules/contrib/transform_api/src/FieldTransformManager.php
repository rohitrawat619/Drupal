<?php

namespace Drupal\transform_api;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The field transform manager.
 */
class FieldTransformManager extends DefaultPluginManager {

  /**
   * An array of transform options for each field type.
   *
   * @var array
   */
  protected $transformOptions;

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The service container.
   *
   * @var \Drupal\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a FieldTransformManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, FieldTypePluginManagerInterface $field_type_manager, ContainerInterface $container) {
    parent::__construct(
      'Plugin/Transform/Field',
      $namespaces,
      $module_handler,
      'Drupal\transform_api\FieldTransformInterface',
      'Drupal\transform_api\Annotation\FieldTransform'
    );
    $this->alterInfo('transform_api_field_info');
    $this->setCacheBackend($cache_backend, 'transform_api_field_plugins');
    $this->fieldTypeManager = $field_type_manager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // @todo This is copied from \Drupal\Core\Plugin\Factory\ContainerFactory.
    //   Find a way to restore sanity to
    //   \Drupal\Core\Field\FormatterBase::__construct().
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create($this->container, $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['transform_mode'], $configuration['third_party_settings']);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - field_definition: (FieldDefinitionInterface) The field definition.
   *   - transform_mode: (string) The transform mode.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the formatter. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - type: (string) The formatter to use. Defaults to the
   *       'default_formatter' for the field type, The default formatter will
   *       also be used if the requested formatter is not available.
   *     - settings: (array) Settings specific to the formatter. Each setting
   *       defaults to the default value specified in the formatter definition.
   *     - third_party_settings: (array) Settings provided by other extensions
   *       through hook_field_formatter_third_party_settings_form().
   *
   * @return \Drupal\Core\Field\FormatterInterface|null
   *   A formatter object or NULL when plugin is not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    $field_definition = $options['field_definition'];
    $field_type = $field_definition->getType();

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] === TRUE) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default formatter if either:
    // - the configuration does not specify a formatter class
    // - the field type is not allowed for the formatter
    // - the formatter is not applicable to the field definition.
    $definition = $this->getDefinition($configuration['type'], FALSE);
    if (!isset($definition['class']) || !in_array($field_type, $definition['field_types']) || !$definition['class']::isApplicable($field_definition)) {
      // Grab the default widget for the field type.
      $field_type_definition = $this->fieldTypeManager->getDefinition($field_type);
      if (empty($field_type_definition['default_transform'])) {
        return NULL;
      }
      $plugin_id = $field_type_definition['default_transform'];
    }

    $configuration += [
      'field_definition' => $field_definition,
      'transform_mode' => $options['transform_mode'],
    ];
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $configuration
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += [
      'label' => '',
      'settings' => [],
      'third_party_settings' => [],
    ];
    // If no formatter is specified, use the default formatter.
    if (!isset($configuration['type'])) {
      $field_type = $this->fieldTypeManager->getDefinition($field_type);
      $configuration['type'] = $field_type['default_transform'];
    }
    // Filter out unknown settings, and fill in defaults for missing settings.
    $default_settings = $this->getDefaultSettings($configuration['type']);
    $configuration['settings'] = array_intersect_key($configuration['settings'], $default_settings) + $default_settings;

    return $configuration;
  }

  /**
   * Returns an array of formatter options for a field type.
   *
   * @param string|null $field_type
   *   (optional) The name of a field type, or NULL to retrieve all formatters.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all formatters,
   *   keyed by field type.
   */
  public function getOptions($field_type = NULL) {
    if (!isset($this->transformOptions)) {
      $options = [];
      $field_types = $this->fieldTypeManager->getDefinitions();
      $transform_types = $this->getDefinitions();
      uasort($transform_types, [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]);
      foreach ($transform_types as $name => $transform_type) {
        foreach ($transform_type['field_types'] as $formatter_field_type) {
          // Check that the field type exists.
          if (isset($field_types[$formatter_field_type])) {
            $options[$formatter_field_type][$name] = $transform_type['label'];
          }
        }
      }
      $this->transformOptions = $options;
    }
    if ($field_type) {
      return !empty($this->transformOptions[$field_type]) ? $this->transformOptions[$field_type] : [];
    }
    return $this->transformOptions;
  }

  /**
   * Returns the default settings of a field formatter.
   *
   * @param string $type
   *   A field formatter type name.
   *
   * @return array
   *   The formatter type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getDefaultSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      /** @var \Drupal\transform_api\FieldTransformInterface $plugin_class */
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultSettings();
    }
    return [];
  }

}
