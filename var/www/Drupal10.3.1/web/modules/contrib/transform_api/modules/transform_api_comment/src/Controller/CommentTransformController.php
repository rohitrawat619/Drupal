<?php

namespace Drupal\transform_api_comment\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for handling transform comment routes.
 */
class CommentTransformController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $user;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private TimeInterface $time;

  /**
   * Construct a CommentTransformController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The time service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(AccountProxyInterface $user, TimeInterface $time) {
    $this->user = $user;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('datetime.time')
    );
  }

  /**
   * Add a comment to an entity on a comment field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $entity_type_id
   *   The entity type of the entity.
   * @param string $entity_id
   *   The entity id of the entity.
   * @param string $field_name
   *   The comment field name on the entity.
   * @param string $comment_type
   *   The type of comment to add.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response to tell whether the operation was successful.
   */
  public function add(Request $request, $entity_type_id, $entity_id, $field_name, $comment_type) {
    if ($this->user->hasPermission('post comments')) {
      $values = [];
      $content = $request->getContent();
      if (!empty($content)) {
        $values = json_decode($content, TRUE);
      }
      $values['entity_type'] = $entity_type_id;
      $values['entity_id'] = $entity_id;
      $values['field_name'] = $field_name;
      $values['comment_type'] = $comment_type;

      $comment = Comment::create($values);
      $comment->setCreatedTime($this->time->getRequestTime());
      $comment->setSubject($values['subject'] ?? '');
      // Empty author ID should revert to anonymous.
      $author_id = $values['uid'];
      if ($comment->id() && $this->currentUser->hasPermission('administer comments')) {
        // Admin can leave the author ID blank to revert to anonymous.
        $author_id = $author_id ?: 0;
      }
      if (!is_null($author_id)) {
        if ($author_id === 0 && $this->user->isAnonymous()) {
          // Use the author name value when the form has access to the element
          // and the author ID is anonymous.
          $comment->setAuthorName($values['name']);
        }
        else {
          // Ensure the author name is not set.
          $comment->setAuthorName(NULL);
        }
      }
      else {
        $author_id = $this->user->id();
      }
      $comment->setOwnerId($author_id);

      // Validate the comment's subject. If not specified, extract from comment
      // body.
      if (trim($comment->getSubject()) == '') {
        if ($comment->hasField('comment_body')) {
          // The body may be in any format, so:
          // 1) Filter it into HTML
          // 2) Strip out all HTML tags
          // 3) Convert entities back to plain-text.
          $comment_text = $comment->comment_body->processed;
          $comment->setSubject(Unicode::truncate(trim(Html::decodeEntities(strip_tags($comment_text))), 29, TRUE, TRUE));
        }
        // Edge cases where the comment body is populated only by HTML tags will
        // require a default subject.
        if ($comment->getSubject() == '') {
          $comment->setSubject($this->t('(No subject)'));
        }
      }

      try {
        $comment->save();
      }
      catch (EntityStorageException $e) {
        return new Response($e->getMessage(), 400);
      }
      return new Response($comment->id(), 200);
    }
    else {
      return new Response(NULL, 403);
    }
  }

}
