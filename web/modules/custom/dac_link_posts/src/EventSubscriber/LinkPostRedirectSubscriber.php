<?php

namespace Drupal\dac_link_posts\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects a link post's own page to the page it links to.
 *
 * Temporary (302), so changing the "Links to" field later is not undone by
 * browsers that cached an old permanent redirect. The edit form and
 * revisions are on other routes, so editors can still reach the post.
 */
class LinkPostRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(protected RouteMatchInterface $routeMatch) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After routing (32), so the node parameter is upcast.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

  /**
   * Sends visitors on a link post's canonical route to its target.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest() || $this->routeMatch->getRouteName() !== 'entity.node.canonical') {
      return;
    }
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface || !($target = dac_link_posts_target($node))) {
      return;
    }
    $url = $target->setAbsolute()->toString(TRUE);
    $response = new TrustedRedirectResponse($url->getGeneratedUrl(), 302);
    $response->addCacheableDependency($url);
    $response->addCacheableDependency(CacheableMetadata::createFromObject($node));
    $event->setResponse($response);
  }

}
