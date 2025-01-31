<?php

namespace Drupal\transform_api;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of transform block entities.
 *
 * @see \Drupal\transform_api\Entity\TransformBlock
 */
class TransformBlockListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The transform blocks service.
   *
   * @var \Drupal\transform_api\TransformBlocks
   */
  private TransformBlocks $transformBlocks;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\transform_api\TransformBlocks $transformBlocks
   *   The transform blocks service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder, MessengerInterface $messenger, TransformBlocks $transformBlocks) {
    parent::__construct($entity_type, $storage);
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
    $this->limit = FALSE;
    $this->transformBlocks = $transformBlocks;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('form_builder'),
      $container->get('messenger'),
      $container->get('transform_api.transform_blocks')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The block list as a renderable array.
   */
  public function render(Request $request = NULL) {
    $this->request = $request;

    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transform_block_admin_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attributes']['class'][] = 'clearfix';

    // Build the form tree.
    $form['blocks'] = $this->buildBlocksForm();

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save blocks'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds the main "Blocks" portion of the form.
   *
   * @return array
   *   Form array.
   */
  protected function buildBlocksForm() {
    // Build blocks first for each region.
    $blocks = [];
    $entities = $this->load();
    /** @var TransformBlockInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $definition = $entity->getPlugin()->getPluginDefinition();
      $blocks[$entity->getRegion()][$entity_id] = [
        'label' => $entity->label(),
        'entity_id' => $entity_id,
        'weight' => $entity->getWeight(),
        'entity' => $entity,
        'category' => $definition['category'],
        'status' => $entity->status(),
      ];
    }

    $form = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get a unique weight.
    $weight_delta = round(count($entities) / 2);

    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $form['#attached']['drupalSettings']['blockPlacement'] = $placement;
      // Remove the block placement from the current request so that it is not
      // passed on to any redirect destinations.
      $this->request->query->remove('block-placement');
    }

    // Loop over each region and build blocks.
    $regions = $this->transformBlocks->getRegions();
    foreach ($regions as $region => $title) {
      $form['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];
      $form['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      $form['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region],
          'no_striping' => TRUE,
        ],
      ];
      $form['region-' . $region]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ],
        ],
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => $this->t('Place block <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('transform_api.transform_block.admin_library', [], ['query' => ['region' => $region]]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 880,
          ]),
        ],
      ];

      $form['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            empty($blocks[$region]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $form['region-' . $region . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region])) {
        foreach ($blocks[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form[$entity_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
          ];
          $form[$entity_id]['#attributes']['class'][] = $info['status'] ? 'block-enabled' : 'block-disabled';
          if ($placement && $placement == Html::getClass($entity_id)) {
            $form[$entity_id]['#attributes']['class'][] = 'color-success';
            $form[$entity_id]['#attributes']['class'][] = 'js-block-placed';
          }
          $form[$entity_id]['info'] = [
            '#wrapper_attributes' => [
              'class' => ['block'],
            ],
          ];
          // Ensure that the label is always rendered as plain text. Render
          // array #plain_text key is essentially treated same as @ placeholder
          // in translatable markup.
          if ($info['status']) {
            $form[$entity_id]['info']['#plain_text'] = $info['label'];
          }
          else {
            $form[$entity_id]['info']['#markup'] = $this->t('@label (disabled)', ['@label' => $info['label']]);
          }

          $form[$entity_id]['type'] = [
            '#markup' => $info['category'],
          ];
          $form[$entity_id]['region-theme']['region'] = [
            '#type' => 'select',
            '#default_value' => $region,
            '#required' => TRUE,
            '#title' => $this->t('Region for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#options' => $regions,
            '#attributes' => [
              'class' => ['block-region-select', 'block-region-' . $region],
            ],
            '#parents' => ['blocks', $entity_id, 'region'],
          ];
          $form[$entity_id]['weight'] = [
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @block block', ['@block' => $info['label']]),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['block-weight', 'block-weight-' . $region],
            ],
          ];
          $form[$entity_id]['operations'] = $this->buildOperations($info['entity']);
        }
      }
    }

    // Do not allow disabling the main system content block when it is present.
    if (isset($form['system_main']['region'])) {
      $form['system_main']['region']['#required'] = TRUE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    if (isset($operations['delete'])) {
      $operations['delete']['title'] = $this->t('Remove');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('blocks'))) {
      $form_state->setErrorByName('blocks', $this->t('No blocks settings to update.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $blocks = $form_state->getValue('blocks');
    $entities = $this->storage->loadMultiple(array_keys($blocks));
    /** @var \Drupal\block\BlockInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(['blocks', $entity_id]);
      $entity->setWeight($entity_values['weight']);
      $entity->setRegion($entity_values['region']);
      try {
        $entity->save();
      }
      catch (EntityStorageException) {
        $this->messenger()->addError($this->t('Unable to save transform block positions.'));
      }
    }
    $this->messenger()->addStatus($this->t('The block settings have been updated.'));
  }

}
