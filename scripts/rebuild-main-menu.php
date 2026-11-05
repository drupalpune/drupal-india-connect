<?php

/**
 * @file
 * Regroups the main menu (issue #165).
 *
 * Run: ddev drush php:script scripts/rebuild-main-menu.php
 *
 * Menu links are content, not config, so deploys do not carry this: run it
 * against each environment's database by hand (back it up first). On the
 * server, prefix drush with `php -d disable_functions=`.
 *
 * Before:
 *   Program ▾  Community ▾  Sponsors ▾  Blogs  Key Dates  Travel & Stay
 * After:
 *   Program ▾  AI Summit ▾  Plan your visit ▾  Community ▾  Sponsors ▾  News ▾
 *
 * Links are matched on their URI, not their id, and page URIs are resolved
 * from their aliases, because ids can differ between environments. Existing links are moved and relabelled rather than
 * recreated, and the three new group headings are created only if missing,
 * so the script is safe to run twice. The footer's link columns are built
 * from this menu (menu--footer.html.twig) and follow it automatically.
 *
 * The visa page (/visa-information) is unpublished until it is reviewed; its
 * link is added now and stays hidden from visitors until then.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$menu = 'main';

/**
 * Loads the main-menu link pointing at $uri, or NULL.
 */
$find = function (string $uri, ?string $title = NULL) use ($storage, $menu): ?MenuLinkContent {
  $query = $storage->getQuery()->accessCheck(FALSE)
    ->condition('menu_name', $menu)
    ->condition('link.uri', $uri);
  if ($title !== NULL) {
    $query->condition('title', $title);
  }
  $ids = $query->execute();
  if (count($ids) > 1) {
    throw new \RuntimeException("More than one main-menu link points at $uri");
  }
  return $ids ? $storage->load(reset($ids)) : NULL;
};

/**
 * Loads a link by URI and fails loudly if it is not there.
 */
$require = function (string $uri, ?string $title = NULL) use ($find): MenuLinkContent {
  $link = $find($uri, $title);
  if (!$link) {
    throw new \RuntimeException("No main-menu link points at $uri" . ($title ? " ($title)" : ''));
  }
  return $link;
};

/**
 * Finds or creates the link at $uri, then sets its place and label.
 */
$place = function (string $uri, string $title, ?MenuLinkContent $parent, int $weight, ?string $match_title = NULL) use ($find, $menu): MenuLinkContent {
  $link = $find($uri, $match_title ?? ($uri === 'route:<nolink>' ? $title : NULL));
  $action = $link ? 'updated' : 'created';
  $link ??= MenuLinkContent::create(['menu_name' => $menu, 'link' => ['uri' => $uri]]);
  $link->set('title', $title);
  $link->set('parent', $parent ? 'menu_link_content:' . $parent->uuid() : '');
  $link->set('weight', $weight);
  $link->set('enabled', TRUE);
  $link->save();
  echo sprintf("%s %-30s %s\n", $action, ($parent ? '  ' : '') . $title, $uri);
  return $link;
};

/**
 * Turns a page alias into an entity: URI, since entity ids are per-site.
 */
$entity_uri = function (string $alias): string {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
  if (!preg_match('#^/(page|node)/(\d+)$#', $path, $m)) {
    throw new \RuntimeException("$alias is not a page alias on this site");
  }
  return ($m[1] === 'page' ? 'entity:canvas_page/' : 'entity:node/') . $m[2];
};

// Top level. Program, Community and Sponsors keep their links and children.
$program = $require('route:<nolink>', 'Program');
$community = $require('internal:/meet-the-organizers', 'Community');
$sponsors = $require('route:<nolink>', 'Sponsors');

$program->set('weight', -50)->save();
$summit = $place('route:<nolink>', 'AI Summit', NULL, -49);
$visit = $place('route:<nolink>', 'Plan your visit', NULL, -48);
$community->set('weight', -47)->save();
$sponsors->set('weight', -46)->save();
$news = $place('route:<nolink>', 'News', NULL, -45);

// AI Summit: was a child of Program, with the panel page one level below it
// (deeper than the menu blocks render).
$place('internal:/ai-summit', 'Summit overview', $summit, -50);
$place('internal:/ai-summit/building-intelligent-public-infrastructure', 'Building intelligent public infrastructure', $summit, -49);

// Plan your visit. FAQ reuses the disabled top-level homepage-anchor link.
$place($entity_uri('/travel-and-stay'), 'Venue & travel', $visit, -50);
$place($entity_uri('/visa-information'), 'Visa information', $visit, -49);
$place($entity_uri('/code-of-conduct'), 'Code of conduct', $visit, -48);
$place('internal:/#faq', 'FAQ', $visit, -47);

// News: Key dates is the Announcements page (participation deadlines, not
// trip planning), so it sits next to the blog.
$place('internal:/blogs', 'Blog', $news, -50);
$place('internal:/announcements', 'Key dates', $news, -49);

\Drupal::service('plugin.manager.menu.link')->rebuild();
drupal_flush_all_caches();
