<?php

namespace Drupal\example_rest\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "example_get_rest_resource",
 *   label = @Translation("Example get rest resource"),
 *   uri_paths = {
 *     "canonical" = "/get-rest/{nid}",
 *     "create" = "/post-rest"
 *   }
 * )
 */
class ExampleGetRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('example_rest'),
      $container->get('current_user')
    );
  }
  
  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */

  public function post($data) {
      $node = Node::create(
        [
          'type' => $data[0]['nodetype'],
          'title' => $data[0]['title'],
          'body' => [
            'value' => $data[0]['body'],
            'format' => 'full_html',
          ],
          'field_counting' => $data[0]['counting'],
        ]
      );
      $node->save();

      $this->logger->notice($this->t("Node with nid @nid saved!\n", ['@nid' => $node->id()]));
      $nodes[] = $node->id();

    $message = $this->t("New Nodes Created with nids : @message", ['@message' => implode(",", $nodes)]);
    return new ResourceResponse($message, 200);
  }

   public function delete($nid) {

      $node = Node::load($nid);
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

      if ($node) {
      $node->delete();  
    }
      $this->logger->notice($this->t("Node with nid @nid deleted!\n", ['@nid' => $node->id()]));
      $nodes[] = $node->id();

    $message = $this->t("Nodes Deleted with nids : @message", ['@message' => implode(",", $nodes)]);
    return new ResourceResponse($message, 200);
  }

  public function get($nid) {
 
    $node = Node::load($nid);

    if ($node) {

      foreach($node->field_counting as $counting) {
        $count[] = [$counting->value];
      }
    
      $result = [
        "id" => $node->id(),
        "title" => $node->getTitle(),
        "body" => $node->get('body')->value,
        "counting" => $count
      ];
    }
      $response = new ResourceResponse($result);
      $response->addCacheableDependency($result);

      return $response;
  }

public function patch($nid, $data) {
    $node = Node::load($nid);
    if (!$node) {
        throw new BadRequestHttpException("Node with ID $nid not found.");
    }

    // Update only provided fields
    if (isset($data['title'])) {
        $node->setTitle($data['title']);
    }
    if (isset($data['body'])) {
        $node->set('body', [
            'value' => $data['body'],
            'format' => 'full_html',
        ]);
    }
    if (!empty($data['counting']) && is_array($data['counting'])) {
      $values = [];
      foreach ($data['counting'] as $value) {
          // Ensure it's not a nested array
          if (is_array($value)) {
              foreach ($value as $inner_value) {
                  $values[] = ['value' => $inner_value];
              }
          } else {
              $values[] = ['value' => $value];
          }
      }
      $node->set('field_counting', $values);
    }

    $node->save();
    return new ResourceResponse(["message" => "Node $nid patched successfully"], 200);
}

  }