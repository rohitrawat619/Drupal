<?php

namespace Drupal\transform_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\transform_api\TransformBlocks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overview form for transform regions.
 */
class TransformBlockRegionOverviewForm extends ConfigFormBase {

  /**
   * The transform blocks service.
   *
   * @var \Drupal\transform_api\TransformBlocks
   */
  private TransformBlocks $transformBlocks;

  /**
   * Construct a TransformBlockRegionOverviewForm object.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transform_api.transform_blocks.region_overview';
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['regions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'transform-blocks-regions',
      ],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'draggable-weight',
      ],
      ],
    ];

    $delta = 0;
    $region = '';
    foreach ($this->transformBlocks->getRegions() as $region => $label) {
      $form['regions'][$region]['#attributes']['class'][] = 'draggable';
      $form['regions'][$region]['region'] = [
        '#type' => 'item',
        '#title' => $label,
        '#default_value' => $region,
      ];
      $form['regions'][$region]['weight'] = [
        '#type' => 'weight',
        '#weight' => $delta,
        '#title' => $this->t('Weight for content'),
        '#title_display' => 'invisible',
        '#default_value' => $delta,
        '#attributes' => ['class' => ['draggable-weight']],
      ];
      $form['regions'][$region]['operations'] = [
        '#type' => 'operations',
        '#links' => $this->getRegionOperations($region),
      ];
      $delta++;
    }

    $form['default_region'] = [
      '#type' => 'select',
      '#title' => $this->t('Default region'),
      '#options' => $this->transformBlocks->getRegions(),
      '#default_value' => $this->transformBlocks->getDefaultRegion(),
    ];

    $form['collapse_regions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapse regions'),
      '#default_value' => $this->transformBlocks->shouldRegionsCollapse(),
      '#description' => $this->t(
        'If region contains only one block,
        this setting reduces nesting by removing the block wrapper. <br>
        <b>Notice:</b> When additional blocks are added to the region,
        the wrapper will be restored, and JSON structure will be different.'
      ),
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
   * Return operations for a given region.
   *
   * @param string $region
   *   The region to deliver operations for.
   *
   * @return array
   *   List of operations.
   */
  protected function getRegionOperations($region) {
    $operations = [];

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'weight' => 10,
      'url' => Url::fromRoute('transform_api.transform_blocks.region_edit', ['region' => $region]),
    ];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'weight' => 100,
      'url' => Url::fromRoute('transform_api.transform_blocks.region_delete', ['region' => $region]),
    ];

    return $operations;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('transform_api.regions');
    $regions = $config->get('regions') ?? [];
    $result = [];
    foreach ($form_state->getValue('regions') as $region => $item) {
      $result[$region] = $regions[$region];
    }
    $config->set('regions', $result);
    $config->set('default_region', $form_state->getValue('default_region'));
    $config->set('collapse_regions', $form_state->getValue('collapse_regions'));
    $config->save();
  }

}
