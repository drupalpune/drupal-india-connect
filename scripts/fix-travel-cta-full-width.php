<?php

/**
 * @file
 * Makes the Travel & Stay "See you in Mumbai" CTA band full browser width.
 *
 * The CTA was a top-level row carrying its own background, which only fills
 * the content column. This wraps it in a full-width section that carries the
 * colour instead, the same settings as the AI Summit page's closing CTA.
 * build-travel-page.php builds it that way now; this fixes existing pages.
 *
 * Dry run (default), then apply:
 *   ddev drush php:script scripts/fix-travel-cta-full-width.php
 *   ddev drush php:script scripts/fix-travel-cta-full-width.php apply
 *
 * Content, not config: run it against each environment's database after
 * backing it up. Safe to re-run; it stops if the CTA is already wrapped.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/travel-and-stay';
$heading = 'See you in Mumbai';
$apply = ($extra[0] ?? '') === 'apply';

$etm = \Drupal::entityTypeManager();
$storage = $etm->getStorage('canvas_page');
$path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
if (!preg_match('#^/page/(\d+)$#', $path, $m)) {
  echo "No canvas_page at $alias.\n";
  return;
}
$page = $storage->load($m[1]);

if (\Drupal::keyValue('canvas.auto_save')->get("canvas_page:{$page->id()}:en")) {
  echo "canvas_page {$page->id()} has an unsaved Canvas editor draft. Publish or discard it first, or it will overwrite this change.\n";
  return;
}

$rows = $page->get('components')->getValue();
$inputs = fn(array $row) => is_string($row['inputs']) ? json_decode($row['inputs'], TRUE) : $row['inputs'];

$index = NULL;
foreach ($rows as $i => $row) {
  if ($row['component_id'] === 'sdc.event_horizon.cta' && trim($inputs($row)['heading_text'] ?? '') === $heading) {
    $index = $i;
  }
}
if ($index === NULL) {
  echo "No '$heading' CTA on canvas_page {$page->id()}.\n";
  return;
}
if ($rows[$index]['parent_uuid'] !== NULL) {
  echo "The '$heading' CTA is already inside a component. Nothing to do.\n";
  return;
}

$section_entity = $etm->getStorage('component')->load('sdc.event_horizon.section');
$cta_inputs = $inputs($rows[$index]);
$background = $cta_inputs['background_color'] ?? 'secondary';
unset($cta_inputs['background_color']);

$section_uuid = (new UuidGen())->generate();
$section_row = [
  'uuid' => $section_uuid,
  'parent_uuid' => NULL,
  'slot' => NULL,
  'component_id' => 'sdc.event_horizon.section',
  'component_version' => $section_entity->getActiveVersion(),
  'inputs' => json_encode([
    'columns' => '100',
    'background_color' => $background,
    'width' => '100%',
    'mobile_columns' => '1',
    'margin_block_start' => '0',
    'margin_block_end' => '0',
    'padding_block_start' => '0',
    'padding_block_end' => '0',
    'section_header' => FALSE,
    'section_footer' => FALSE,
    'full_width' => TRUE,
    'dark_background' => FALSE,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
  'label' => "Section - CTA $heading",
];

$new = $rows;
$new[$index]['parent_uuid'] = $section_uuid;
$new[$index]['slot'] = 'main_slot';
$new[$index]['inputs'] = json_encode($cta_inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
// The section takes the CTA's place among the top-level rows.
array_splice($new, $index, 0, [$section_row]);

$page->set('components', $new);
$violations = $page->validate();
foreach ($violations as $v) {
  echo 'VIOLATION ', $v->getPropertyPath(), ': ', strip_tags((string) $v->getMessage()), "\n";
}
echo "canvas_page {$page->id()}: wrap '$heading' CTA in a full-width $background section (", count($rows), ' -> ', count($new), " rows).\n";
if (count($violations)) {
  echo "Not saved.\n";
  return;
}
if (!$apply) {
  echo "Dry run. Pass 'apply' to save.\n";
  return;
}

$page->setNewRevision(TRUE);
$page->setRevisionLogMessage("Make the '$heading' CTA band full width.");
$page->save();

$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$after = array_column($saved->get('components')->getValue(), 'uuid');
$lost = array_diff(array_column($new, 'uuid'), $after);
echo 'Saved: ', count($after), " rows.\n";
echo $lost ? 'LOST ' . count($lost) . ' rows: ' . implode(', ', $lost) . "\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
