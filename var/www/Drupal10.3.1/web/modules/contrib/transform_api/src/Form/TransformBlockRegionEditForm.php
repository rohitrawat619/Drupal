<?php

namespace Drupal\transform_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\transform_api\TransformBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for transform regions.
 */
class TransformBlockRegionEditForm extends ConfigFormBase {

  /**
   * The transform blocks service.
   *
   * @var \Drupal\transform_api\TransformBlocks
   */
  private TransformBlocks $transformBlocks;

  /**
   * Construct a TransformBlockRegionEditForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\transform_api\TransformBlocks $transformBlocks
   *   The transform blocks service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TransformBlocks $transformBlocks) {
    parent::__construct($config_factory);
    $this->transformBlocks = $transformBlocks;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('transform_api.transform_blocks')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'transform_api.transform_blocks.region_form';
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['transform_api.regions'];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $region = '') {
    $form = [];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->transformBlocks->getRegions()[$region] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Name of the new region.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 255,
      '#default_value' => $region,
      '#required' => TRUE,
      '#disabled' => !($region == ''),
      '#machine_name' => [
        'exists' => 'Drupal\transform_api\TransformBlocks::regionExists',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($region == '') ? $this->t('Add region') : $this->t('Save changes'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('transform_api.regions');
    $regions = $config->get('regions') ?? [];
    $regions[$form_state->getValue('id')] = $form_state->getValue('label');
    $config->set('regions', $regions);
    $config->save();
    $form_state->setRedirect('transform_api.transform_blocks.regions');
  }

}
