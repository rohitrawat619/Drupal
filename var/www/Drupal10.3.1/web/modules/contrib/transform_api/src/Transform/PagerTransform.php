<?php

namespace Drupal\transform_api\Transform;

/**
 * A transform for a pager.
 */
class PagerTransform extends TransformBase {

  /**
   * Construct a PagerTransform.
   *
   * @param int $element
   *   The pager element that should be transformed.
   */
  public function __construct($element = 0) {
    /** @var \Drupal\Core\Pager\PagerManagerInterface $pagerManager */
    $pagerManager = \Drupal::service('pager.manager');
    $pager = $pagerManager->getPager($element);
    if (empty($pager)) {
      $this->values = [];
    }
    else {
      $this->values = [
        'current' => $pager->getCurrentPage(),
        'limit' => $pager->getLimit(),
        'items' => $pager->getTotalItems(),
        'pages' => $pager->getTotalPages(),
      ];
    }
  }

  /**
   * Set the current page.
   *
   * @param int $current
   *   The current page.
   */
  public function setCurrentPage($current) {
    $this->setValue('current', $current);
  }

  /**
   * Set the page limit.
   *
   * @param int $limit
   *   The page limit.
   */
  public function setLimit($limit) {
    $this->setValue('limit', $limit);
  }

  /**
   * Set the total number of items.
   *
   * @param int $items
   *   The total number of items.
   */
  public function setTotalItems($items) {
    $this->setValue('items', $items);
  }

  /**
   * Set the total number of pages.
   *
   * @param int $pages
   *   The total number of pages.
   */
  public function setTotalPages($pages) {
    $this->setValue('pages', $pages);
  }

  /**
   * {@inheritDoc}
   */
  public function getTransformType() {
    return 'pager';
  }

  /**
   * {@inheritDoc}
   */
  public function transform() {
    if (empty($this->values)) {
      return [];
    }
    else {
      return [
        'current' => intval($this->getValue('current')),
        'limit' => intval($this->getValue('limit')),
        'items' => intval($this->getValue('items')),
        'pages' => intval($this->getValue('pages')),
      ];
    }
  }

}
