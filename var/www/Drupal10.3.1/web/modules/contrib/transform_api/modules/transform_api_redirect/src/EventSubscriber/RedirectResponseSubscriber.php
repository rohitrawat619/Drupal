<?php

namespace Drupal\transform_api_redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirect subscriber for transform_api requests.
 */
class RedirectResponseSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * Construct a RedirectResponseSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(ResponseEvent $event) {
    if ($event->getResponse() instanceof TrustedRedirectResponse) {
      /** @var \Drupal\Core\Routing\TrustedRedirectResponse $response */
      $response = $event->getResponse();
      if (str_contains($response->getTargetUrl(), 'format=json')) {
        $parts = UrlHelper::parse($response->getTargetUrl());
        unset($parts['query']['format']);
        unset($parts['query']['redirect']);
        $target = Url::fromUri($parts['path'], [
          'query' => $parts['query'],
          'fragment' => $parts['fragment'],
        ])->setAbsolute(FALSE)->toString();
        $host = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
        $redirect = [
          'type' => 'redirect',
          'status_code' => $response->getStatusCode(),
          'target' => str_replace($host, '', $target),
        ];
        $event->setResponse(new JsonResponse($redirect));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelRequestCheckRedirect', 0];
    return $events;
  }

}
