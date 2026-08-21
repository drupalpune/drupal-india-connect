#!/usr/bin/env php
<?php

/**
 * @file
 * Detects duplicate Drupal Canvas folder config before it reaches
 * config/sync.
 *
 * Canvas auto-creates a Folder entity by *name* the first time a component
 * is saved into a category that doesn't have one yet
 * (Component::postSave(), web/modules/contrib/canvas/src/Entity/Component.php).
 * Drupal's config system tracks Folder entities by UUID, so if discovery
 * ever creates a same-named folder before the "real" one from config/sync
 * gets imported, both survive as separate config entities forever -
 * config import has no way to know they're "the same folder". A bare
 * `drush cex` then happily commits both.
 *
 * The symptom shows up much later and far from the cause: every
 * `drush cron` run throws
 *   RuntimeException: It is impossible for an item to exist in multiple
 *   Folders. in Drupal\canvas\Entity\Folder::loadByItemAndConfigEntityTypeId()
 * because Canvas itself forbids a component being listed in more than one
 * folder - a rule config import never enforces.
 *
 * Run this against a scratch config export before copying anything into
 * config/sync (see the "Config" section of CLAUDE.md), or point it at
 * config/sync itself to check what's already committed:
 *
 *   php scripts/check-canvas-folder-duplicates.php config/sync
 *   php scripts/check-canvas-folder-duplicates.php /var/www/html/cex-full
 *
 * Exits non-zero and prints details if it finds either:
 *   - two or more canvas.folder.*.yml files with the same `name`
 *   - a component id listed in more than one folder's `items`
 *
 * No Drupal bootstrap required - just reads YAML files, so this is safe
 * to run in CI on a pull request without a database.
 */

$dir = $argv[1] ?? 'config/sync';

if (!is_dir($dir)) {
  fwrite(STDERR, "Not a directory: $dir\n");
  exit(2);
}

$files = glob(rtrim($dir, '/') . '/canvas.folder.*.yml');
if (!$files) {
  fwrite(STDERR, "No canvas.folder.*.yml files found in $dir\n");
  exit(0);
}

// Prefer Symfony's parser (already a Drupal dependency) if autoloadable;
// otherwise fall back to PHP's built-in yaml extension or a minimal
// same-purpose scan that doesn't need a full YAML parser.
$autoloaders = [
  __DIR__ . '/../vendor/autoload.php',
  __DIR__ . '/../web/vendor/autoload.php',
];
foreach ($autoloaders as $autoloader) {
  if (file_exists($autoloader)) {
    require_once $autoloader;
    break;
  }
}

function parse_yaml_file(string $path): array {
  if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
    return \Symfony\Component\Yaml\Yaml::parseFile($path) ?? [];
  }
  if (function_exists('yaml_parse_file')) {
    return yaml_parse_file($path) ?: [];
  }
  fwrite(STDERR, "No YAML parser available (need Symfony Yaml via composer, or the php-yaml extension).\n");
  exit(2);
}

$byName = [];   // name => [uuid, ...]
$byItem = [];   // item id => [ [uuid, name], ... ]

foreach ($files as $file) {
  $uuid = preg_replace('/^canvas\.folder\.(.+)\.yml$/', '$1', basename($file));
  $data = parse_yaml_file($file);
  $name = $data['name'] ?? '(no name)';
  $items = $data['items'] ?? [];

  $byName[$name][] = $uuid;
  foreach ($items as $item) {
    $byItem[$item][] = [$uuid, $name];
  }
}

$problems = 0;

foreach ($byName as $name => $uuids) {
  if (count($uuids) > 1) {
    $problems++;
    printf("DUPLICATE FOLDER NAME '%s': %s\n", $name, implode(', ', $uuids));
  }
}

foreach ($byItem as $item => $locations) {
  if (count($locations) > 1) {
    $problems++;
    $where = array_map(fn($loc) => "{$loc[1]} ({$loc[0]})", $locations);
    printf("ITEM IN MULTIPLE FOLDERS '%s': %s\n", $item, implode(' | ', $where));
  }
}

if ($problems === 0) {
  printf("OK: %d folder(s) checked, no duplicate names or cross-listed items.\n", count($files));
  exit(0);
}

printf("\n%d problem(s) found across %d folder(s). See config/sync's \"Config\" section notes in CLAUDE.md.\n", $problems, count($files));
exit(1);
