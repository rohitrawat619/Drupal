<?php

namespace Drupal\transform_api\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the add form for entity display modes.
 *
 * @internal
 */
class EntityDisplayModeAddForm extends EntityDisplayModeFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type_id);

    // Change replace_pattern to avoid undesired dots.
    $form['id']['#machine_name']['replace_pattern'] = '[^a-z0-9_]+';
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    $form['#title'] = $this->t('Add new @entity-type %label',
      [
        '@entity-type' => $definition->getLabel(),
        '%label' => $this->entityType->getSingularLabel(),
      ]
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValueForElement($form['id'], $this->targetEntityTypeId . '.' . $form_state->getValue('id'));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    $definition = $this->entityTypeManager->getDefinition($this->targetEntityTypeId);
    if (!$definition->get('field_ui_base_route') || !$definition->hasViewBuilderClass()) {
      throw new NotFoundHttpException();
    }

    $this->entity->setTargetType($this->targetEntityTypeId);
  }

}
