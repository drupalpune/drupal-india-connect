<?php

declare(strict_types=1);

namespace Drupal\drupalasia_sessions\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\drupalasia_sessions\Cache\Context\ClosedSession403CacheContext;

/**
 * Renders the closed-call 403 in the front-end theme.
 *
 * node/add/session is an admin route, so its 403 would otherwise render in
 * Gin. Speakers reaching it are visitors, not editors: give them the same
 * site chrome as the 404 page.
 */
class ClosedSession403ThemeNegotiator implements ThemeNegotiatorInterface {

  public function __construct(
    protected ClosedSession403CacheContext $closedSession403,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->closedSession403->applies();
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
