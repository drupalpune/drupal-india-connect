<?php

declare(strict_types=1);

namespace Drupal\drupalasia_sessions\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\event_platform_helper\Cache\Context\SessionsOpen;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Whether the current 403 is for /node/add/session while sessions are closed.
 *
 * Every 403 is rendered in a subrequest to the same route, and the dynamic
 * page cache keys that subrequest on its own route. Without this context
 * the first 403 rendered is served for all of them: the closed-call notice
 * leaks onto unrelated 403s, or never appears on the session form.
 *
 * Cache context ID: 'drupalasia_closed_session_403'.
 */
class ClosedSession403CacheContext implements CacheContextInterface {

  public function __construct(
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Closed session submission 403');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->applies() ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * Whether this request is the closed-call 403 for the session form.
   *
   * The 403 page is rendered in a subrequest, so the route that was actually
   * denied is read from the main request.
   */
  public function applies(): bool {
    $current = $this->requestStack->getCurrentRequest();
    if (!$current || !$current->attributes->get('exception') instanceof AccessDeniedHttpException) {
      return FALSE;
    }

    $main = $this->requestStack->getMainRequest();
    if ($main->attributes->get('_route') !== 'node.add') {
      return FALSE;
    }
    $node_type = $main->attributes->get('node_type');
    $bundle = $node_type instanceof NodeTypeInterface ? $node_type->id() : $node_type;

    return $bundle === 'session' && !SessionsOpen::areSessionsOpen();
  }

}
