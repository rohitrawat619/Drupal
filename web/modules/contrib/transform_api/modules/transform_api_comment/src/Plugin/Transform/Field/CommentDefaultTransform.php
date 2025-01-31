<?php

namespace Drupal\transform_api_comment\Plugin\Transform\Field;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\transform_api\Entity\EntityTransformDisplay;
use Drupal\transform_api\FieldTransformBase;
use Drupal\transform_api\Repository\EntityTransformRepositoryInterface;
use Drupal\transform_api\Transform\EntityTransform;
use Drupal\transform_api\Transform\PagerTransform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transform field plugin for comment field types.
 *
 * @FieldTransform(
 *   id = "comment_default",
 *   label = @Translation("Comment list"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentDefaultTransform extends FieldTransformBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'transform_mode' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The comment render controller.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\transform_api\Repository\EntityTransformRepositoryInterface
   */
  protected $entityTransformRepository;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
      $container->get('transform_api.entity_display.repository')
    );
  }

  /**
   * Constructs a new CommentDefaultTransform.
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
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\transform_api\Repository\EntityTransformRepositoryInterface $entityTransformRepository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $transform_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match, EntityTransformRepositoryInterface $entityTransformRepository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $transform_mode, $third_party_settings);
    $this->viewBuilder = $entity_type_manager->getViewBuilder('comment');
    $this->storage = $entity_type_manager->getStorage('comment');
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
    $this->routeMatch = $route_match;
    $this->entityTransformRepository = $entityTransformRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function transformElements(FieldItemListInterface $items, $langcode) {
    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();

    $status = $items->status;
    $elements = [
      'comment_type' => $this->getFieldSetting('comment_type'),
      'comment_display_mode' => $this->getFieldSetting('default_mode'),
      'comments' => [],
      'comment_form' => [],
    ];
    $elements['status'] = match (intval($status)) {
      CommentItemInterface::HIDDEN => 'hidden',
      CommentItemInterface::CLOSED => 'closed',
      CommentItemInterface::OPEN => 'open'
    };

    if ($status != CommentItemInterface::HIDDEN && empty($entity->in_preview)) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      $elements['#cache']['contexts'][] = 'user.permissions';
      if ($this->currentUser->hasPermission('access comments') || $this->currentUser->hasPermission('administer comments')) {
        if ($entity->get($field_name)->comment_count || $this->currentUser->hasPermission('administer comments')) {
          $mode = $comment_settings['default_mode'];
          $comments_per_page = $comment_settings['per_page'];
          $comments = $this->storage->loadThread($entity, $field_name, $mode, $comments_per_page, 9);
          if ($comments) {
            $ids = [];
            foreach ($comments as $comment) {
              $ids[] = $comment->id();
            }
            $elements['pager'] = new PagerTransform(9);
            $elements['comments'] = new EntityTransform('comment', $ids, $this->getSetting('transform_mode'));
          }
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity. Do not show the form for the print view mode.
      if ($status == CommentItemInterface::OPEN && $comment_settings['form_location'] == CommentItemInterface::FORM_BELOW) {
        // Only show the add comment form if the user has permission.
        $elements['#cache']['contexts'][] = 'user.roles';
        if ($this->currentUser->hasPermission('post comments')) {
          $url = Url::fromRoute(
            'transform_api_comment.add',
            [
              'entity_type_id' => $entity->getEntityTypeId(),
              'entity_id' => $entity->id(),
              'field_name' => $field_name,
              'comment_type' => $this->getFieldSetting('comment_type'),
            ]);
          $elements['comment_form'] = [
            'url' => $url->toString(),
          ];
        }
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $transform_modes = $this->getTransformModes();
    $element['transform_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Comments transform mode'),
      '#description' => $this->t('Select the transform mode used to transform the list of comments.'),
      '#default_value' => $this->getSetting('transform_mode'),
      '#options' => $transform_modes,
      // Only show the select element when there are more than one options.
      '#access' => count($transform_modes) > 1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $transform_mode = $this->getSetting('transform_mode');
    $transform_modes = $this->getTransformModes();
    $transform_mode_label = $transform_modes[$transform_mode] ?? 'default';
    return [$this->t('Comment transform mode: @mode', ['@mode' => $transform_mode_label])];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($mode = $this->getSetting('transform_mode')) {
      if ($bundle = $this->getFieldSetting('comment_type')) {
        /** @var \Drupal\transform_api\Configs\EntityTransformDisplayInterface $display */
        if ($display = EntityTransformDisplay::load("comment.$bundle.$mode")) {
          $dependencies[$display->getConfigDependencyKey()][] = $display->getConfigDependencyName();
        }
      }
    }
    return $dependencies;
  }

  /**
   * Provides a list of comment transform modes for the configured comment type.
   *
   * @return array
   *   Associative array keyed by transform mode key and having the transform
   *   mode label as value.
   */
  protected function getTransformModes() {
    return $this->entityTransformRepository->getTransformModeOptionsByBundle('comment', $this->getFieldSetting('comment_type'));
  }

}
