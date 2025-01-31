<?php

namespace Drupal\my_custom_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'My Custom Block' block.
 *
 * @Block(
 *   id = "my_custom_block",
 *   admin_label = @Translation("My Custom Block"),
 * )
 */
class Candidates extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello, this is my custom block!'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Define form fields here if needed.
    $form['my_custom_block_settings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom setting'),
      '#default_value' => $this->configuration['my_custom_block_settings'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['my_custom_block_settings'] = $form_state->getValue('my_custom_block_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'my_custom_block_settings' => '',
    ] + parent::defaultConfiguration();
  }

}