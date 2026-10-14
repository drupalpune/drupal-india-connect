<?php

/**
 * @file
 * Renames the Meet the Team page to Meet the Organizers (issue #120).
 *
 * Run: ddev drush php:script scripts/rename-meet-the-organizers.php
 *
 * - canvas_page title and its "Meet the Team" section heading.
 * - Alias /meet-the-team -> /meet-the-organizers, with a 301 from the old
 *   path so shared links keep working.
 * - Menu links pointing at /meet-the-team (Community and its "Meet the team"
 *   child) move to the new alias; the child is retitled.
 *
 * Deploys are code-only, so run this against each environment's database by
 * hand. Back up the database first. The page is found by alias, not id, and
 * every step checks its current state, so a second run changes nothing.
 */

$old_alias = '/meet-the-team';
$new_alias = '/meet-the-organizers';
$title = 'Meet the Organizers';

$etm = \Drupal::entityTypeManager();
$alias_manager = \Drupal::service('path_alias.manager');
$storage = $etm->getStorage('canvas_page');

// Find the page under either alias.
$path = $alias_manager->getPathByAlias($new_alias);
if ($path === $new_alias) {
  $path = $alias_manager->getPathByAlias($old_alias);
}
if (!preg_match('#^/page/(\d+)$#', $path, $m) || !($page = $storage->load($m[1]))) {
  echo "No canvas_page at $old_alias or $new_alias. Nothing done.\n";
  return;
}
echo "canvas_page {$page->id()} ({$page->label()})\n";

// ---------------------------------------------------------------------------
// Page title, heading and alias, with the guards from CLAUDE.md.
// ---------------------------------------------------------------------------
$rows = $page->get('components')->getValue();
$before = array_column($rows, 'uuid');
$changed = FALSE;

foreach ($rows as &$row) {
  if ($row['component_id'] !== 'sdc.drupal_india_connect.section-heading') {
    continue;
  }
  $inputs = is_string($row['inputs']) ? json_decode($row['inputs'], TRUE) : $row['inputs'];
  if (($inputs['text'] ?? '') !== 'Meet the Team') {
    continue;
  }
  $inputs['text'] = $title;
  $row['inputs'] = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $row['label'] = "$title heading";
  $changed = TRUE;
  echo "Heading: Meet the Team -> $title\n";
}
unset($row);

if ($page->label() !== $title) {
  echo "Title: {$page->label()} -> $title\n";
  $page->set('title', $title);
  $changed = TRUE;
}
if ($changed) {
  $page->set('components', $rows);
  $violations = $page->validate();
  foreach ($violations as $v) {
    echo 'VIOLATION ', $v->getPropertyPath(), ': ', strip_tags((string) $v->getMessage()), "\n";
  }
  if (count($violations)) {
    echo "Not saved.\n";
    return;
  }
  $page->setNewRevision(TRUE);
  $page->setRevisionLogMessage('Rename Meet the Team to Meet the Organizers (issue #120).');
  $page->save();

  $storage->resetCache([$page->id()]);
  $saved = $storage->load($page->id());
  $lost = array_diff($before, array_column($saved->get('components')->getValue(), 'uuid'));
  echo 'Saved: ', count($before), ' rows. ', $lost ? 'LOST ' . count($lost) . " rows\n" : "No rows lost.\n";
  echo 'Post-save violations: ', count($saved->validate()), "\n";
}
else {
  echo "Page already renamed.\n";
}

// ---------------------------------------------------------------------------
// Alias. Edit the existing path_alias entity: setting the page's `path` field
// adds a second alias and leaves the old one resolving.
// ---------------------------------------------------------------------------
$alias_storage = $etm->getStorage('path_alias');
$system_path = '/page/' . $page->id();
$old = $alias_storage->loadByProperties(['path' => $system_path, 'alias' => $old_alias]);
$new = $alias_storage->loadByProperties(['path' => $system_path, 'alias' => $new_alias]);
if ($old && $new) {
  $alias_storage->delete($old);
  echo "Alias: removed duplicate $old_alias\n";
}
elseif ($old) {
  reset($old)->setAlias($new_alias)->save();
  echo "Alias: $old_alias -> $new_alias\n";
}
else {
  echo "Alias already $new_alias.\n";
}

// ---------------------------------------------------------------------------
// Redirect from the old alias. redirect's auto_redirect usually adds this
// when the alias changes; create it if that is off.
// ---------------------------------------------------------------------------
$redirect_storage = $etm->getStorage('redirect');
$source = ltrim($old_alias, '/');
if ($redirect_storage->loadByProperties(['redirect_source__path' => $source])) {
  echo "Redirect from $old_alias exists.\n";
}
else {
  $redirect_storage->create([
    'redirect_source' => $source,
    'redirect_redirect' => 'internal:' . $new_alias,
    'status_code' => 301,
    'language' => 'und',
  ])->save();
  echo "Redirect: $old_alias -> $new_alias (301)\n";
}

// ---------------------------------------------------------------------------
// Menu links.
// ---------------------------------------------------------------------------
$links = $etm->getStorage('menu_link_content')->loadByProperties(['link__uri' => 'internal:' . $old_alias]);
foreach ($links as $link) {
  $link->set('link', ['uri' => 'internal:' . $new_alias] + $link->get('link')->first()->getValue());
  if (strcasecmp($link->getTitle(), 'Meet the team') === 0) {
    $link->set('title', $title);
  }
  $link->save();
  echo "Menu link {$link->id()} ({$link->getTitle()}) -> $new_alias\n";
}
if (!$links) {
  echo "No menu links left on $old_alias.\n";
}
