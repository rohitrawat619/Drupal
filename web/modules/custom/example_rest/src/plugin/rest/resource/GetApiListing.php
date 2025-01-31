<?php

namespace Drupal\example_rest\Plugin\rest\resource;

use Kint\Kint;
use Drupal\Core\Render\Element\Value;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\VarExporter\Internal\Values;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "example_get_rest",
 *   label = @Translation("Example get rest resource"),
 *   uri_paths = {
 *     "canonical" = "/get-rest"
 *   }
 * )
 */
class GetApiListing extends ResourceBase {

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
  
  public function get() {

    $entities = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => ['football']]);

    // $nids = \Drupal::entityQuery('node')->condition('type','football')->execute();
    // $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    
    foreach ($entities as $entity) {
  
      foreach($entity->field_counting as $counting) {
        $count[] = [$counting->value];
      }

  
      $result[$entity->id()] = [
        "title"=>$entity->title->value,
        "counting"=>$count
        ];
      $count = [];
    }
    
    $response = new ResourceResponse($result);
    $response->addCacheableDependency($result);

    return $response;
  }
  }