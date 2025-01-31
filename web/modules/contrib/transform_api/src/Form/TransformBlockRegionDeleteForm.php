<?php

namespace Drupal\transform_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\transform_api\TransformBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete form for transform regions.
 */
class TransformBlockRegionDeleteForm extends ConfirmFormBase {

  use ConfigFormBaseTrait;

  /**
   * The transform blocks service.
   *
   * @var \Drupal\transform_api\TransformBlocks
   */
  private TransformBlocks $transformBlocks;

  /**
   * Construct a TransformBlockRegionDeleteForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\transform_api\TransformBlocks $transformBlocks
   *   The transform blocks service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TransformBlocks $transformBlocks) {
    $this->configFactory = $config_factory;
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
   * Return the current region.
   *
   * @return string
   *   The current region.
   */
  public function getRegion() {
    return \Drupal::routeMatch()->getParameter('region');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %region region?', ['%region' => $this->transformBlocks->getRegions()[$this->getRegion()]]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('transform_api.transform_blocks.regions');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transform_api.transform_blocks.region_delete';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['transform_api.regions'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('transform_api.regions');
    $regions = $config->get('regions') ?? [];
    unset($regions[$this->getRegion()]);
    $config->set('regions', $regions);
    $config->save();
    $form_state->setRedirect('transform_api.transform_blocks.regions');
  }

}
