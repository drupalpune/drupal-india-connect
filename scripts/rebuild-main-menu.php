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
 * Links are matched on where they point, not their id, and pages are named
 * by alias, because ids can differ between environments. Existing links are
 * moved and relabelled rather than recreated, and the three new group
 * headings are created only if missing, so the script is safe to run twice.
 * The footer's link columns are built from this menu (menu--footer.html.twig)
 * and follow it automatically.
 *
 * Menu links to unpublished pages are hidden from visitors, so the visa link
 * only shows once /visa-information is published on that site.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$menu = 'main';

/**
 * Lists the URIs a link to $target can have.
 *
 * A path starting with / is an alias: a link to that page may be stored as
 * entity:canvas_page/N, internal:/page/N or internal:/the-alias, and the
 * entity id can differ between sites. Anything else is a literal URI.
 */
$uris = function (string $target): array {
  if (!str_starts_with($target, '/')) {
    return [$target];
  }
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($target);
  if (!preg_match('#^/(page|node)/(\d+)$#', $path, $m)) {
    throw new \RuntimeException("$target is not a page alias on this site");
  }
  $type = $m[1] === 'page' ? 'canvas_page' : 'node';
  return ["entity:$type/{$m[2]}", "internal:$path", "internal:$target"];
};

/**
 * Loads the main-menu link to $target, or NULL.
 *
 * If several links point there (live had a "Travel & Stay" dropdown whose
 * first child linked to the same page), an enabled one without children is
 * kept and the others are disabled, not deleted, so they can be put back.
 */
$find = function (string $target, ?string $title = NULL) use ($storage, $menu, $uris): ?MenuLinkContent {
  $query = $storage->getQuery()->accessCheck(FALSE)
    ->condition('menu_name', $menu)
    ->condition('link.uri', $uris($target), 'IN')
    ->sort('id');
  if ($title !== NULL) {
    $query->condition('title', $title);
  }
  $links = $storage->loadMultiple($query->execute());
  $has_children = fn(MenuLinkContent $link) => (bool) $storage->getQuery()->accessCheck(FALSE)
    ->condition('parent', 'menu_link_content:' . $link->uuid())
    ->count()->execute();
  usort($links, fn($a, $b) => [!$a->isEnabled(), $has_children($a)] <=> [!$b->isEnabled(), $has_children($b)]);
  $keep = array_shift($links);
  foreach (array_filter($links, fn($extra) => $extra->isEnabled()) as $extra) {
    $extra->set('enabled', FALSE)->save();
    echo sprintf("disabled duplicate %s (id %d) %s\n", $extra->getTitle(), $extra->id(), $extra->link->uri);
  }
  return $keep;
};

/**
 * Loads a link by target and fails loudly if it is not there.
 */
$require = function (string $target, ?string $title = NULL) use ($find): MenuLinkContent {
  $link = $find($target, $title);
  if (!$link) {
    throw new \RuntimeException("No main-menu link points at $target" . ($title ? " ($title)" : ''));
  }
  return $link;
};

/**
 * Finds or creates the link to $target, then sets its place and label.
 */
$place = function (string $target, string $title, ?MenuLinkContent $parent, int $weight) use ($find, $menu, $uris): MenuLinkContent {
  $link = $find($target, $target === 'route:<nolink>' ? $title : NULL);
  $action = $link ? 'updated' : 'created';
  $link ??= MenuLinkContent::create(['menu_name' => $menu, 'link' => ['uri' => $uris($target)[0]]]);
  $link->set('title', $title);
  $link->set('parent', $parent ? 'menu_link_content:' . $parent->uuid() : '');
  $link->set('weight', $weight);
  $link->set('enabled', TRUE);
  $link->save();
  echo sprintf("%s %-30s %s\n", $action, ($parent ? '  ' : '') . $title, $link->link->uri);
  return $link;
};

// Top level. Program, Community and Sponsors keep their links and children.
$program = $require('route:<nolink>', 'Program');
$community = $require('/meet-the-organizers', 'Community');
$sponsors = $require('route:<nolink>', 'Sponsors');

$program->set('weight', -50)->save();
$summit = $place('route:<nolink>', 'AI Summit', NULL, -49);
$visit = $place('route:<nolink>', 'Plan your visit', NULL, -48);
$community->set('weight', -47)->save();
$sponsors->set('weight', -46)->save();
$news = $place('route:<nolink>', 'News', NULL, -45);

// AI Summit: was a child of Program, with the panel page one level below it
// (deeper than the menu blocks render).
$place('/ai-summit', 'Summit overview', $summit, -50);
$place('/ai-summit/building-intelligent-public-infrastructure', 'Building intelligent public infrastructure', $summit, -49);

// Plan your visit. FAQ reuses the disabled top-level homepage-anchor link.
$place('/travel-and-stay', 'Venue & travel', $visit, -50);
$place('/visa-information', 'Visa information', $visit, -49);
$place('/code-of-conduct', 'Code of conduct', $visit, -48);
$place('internal:/#faq', 'FAQ', $visit, -47);

// News: Key dates is the Announcements page (participation deadlines, not
// trip planning), so it sits next to the blog.
$place('internal:/blogs', 'Blog', $news, -50);
$place('/announcements', 'Key dates', $news, -49);

\Drupal::service('plugin.manager.menu.link')->rebuild();
drupal_flush_all_caches();
