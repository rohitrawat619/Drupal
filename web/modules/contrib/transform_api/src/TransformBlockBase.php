<?php

namespace Drupal\transform_api;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for transform block plugins.
 */
abstract class TransformBlockBase extends PluginBase implements TransformBlockPluginInterface, PluginWithFormsInterface, ContextAwarePluginInterface {

  use StringTranslationTrait;
  use ContextAwarePluginTrait;
  use ContextAwarePluginAssignmentTrait;
  use MessengerTrait;
  use PluginWithFormsTrait;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Get the label for the block.
   *
   * @return string
   *   The label for the block.
   */
  public function label() {
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['admin_label'];
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for block plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
      'label' => '',
      'provider' => $this->pluginDefinition['provider'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->blockAccess($account);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Indicates whether the block should be shown.
   *
   * Blocks with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see self::access()
   */
  protected function blockAccess(AccountInterface $account) {
    // By default, the block is visible.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];

    $form['admin_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Block description'),
      '#plain_text' => $definition['admin_label'],
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->label(),
      '#required' => TRUE,
    ];

    // Add plugin-specific settings for this block type.
    $form += $this->blockForm($form, $form_state);

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add validation
   * for a specific block type, override BlockBase::blockValidate().
   *
   * @see \Drupal\Core\Block\BlockBase::blockValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');

    $this->blockValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Most block plugins should not override this method. To add submission
   * handling for a specific block type, override BlockBase::blockSubmit().
   *
   * @see \Drupal\Core\Block\BlockBase::blockSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the block's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['label'] = $form_state->getValue('label');
      $this->configuration['label_display'] = $form_state->getValue('label_display');
      $this->configuration['provider'] = $form_state->getValue('provider');
      $this->blockSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliterated = $this->transliteration()->transliterate($admin_label, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);

    return preg_replace('@[^a-z0-9_.]+@', '', $transliterated);
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   The transliteration service.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Sets the transliteration service.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function setTransliteration(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

}
