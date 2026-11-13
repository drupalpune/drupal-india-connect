<?php

/**
 * @file
 * Adds an "After party" card (the social variant) as the last item of Day 2 on /program,
 * after the Closing ceremony.
 *
 * Run: ddev drush php:script scripts/add-program-day2-after-party.php -- apply
 *
 * Every existing row is kept. The Closing ceremony is found by its card's
 * title and Day 2 date, not by uuid, so this runs against any environment.
 * Safe to re-run: an existing After party item is updated in place.
 *
 * scripts/build-program-page.php carries the same entry in $day2, so a
 * rebuild keeps it.
 */

use Drupal\Component\Uuid\Php as UuidGen;

if (($extra[0] ?? '') !== 'apply') {
  echo "Pass `apply` to write: ddev drush php:script scripts/add-program-day2-after-party.php -- apply\n";
  return;
}

$date = '2027-01-29';
$party = [
  'start' => '18:30',
  'end' => '21:00',
  'title' => 'After party (Drupal birthday celebrations)',
  'description' => 'Wind down the day with fellow attendees, speakers and sponsors. Venue and details to be announced.',
];

$etm = \Drupal::entityTypeManager();
$path = \Drupal::service('path_alias.manager')->getPathByAlias('/program');
if (!preg_match('#^/page/(\d+)$#', $path, $m)) {
  throw new \RuntimeException('No canvas_page at /program.');
}
$page = $etm->getStorage('canvas_page')->load($m[1]);
if (\Drupal::keyValue('canvas.auto_save')->get("canvas_page:{$page->id()}:en")) {
  echo "canvas_page {$page->id()} has unpublished Canvas changes. Publish or discard them first.\n";
  return;
}
$rows = $page->get('components')->getValue();
$before = array_column($rows, 'uuid');

$card_of = function (string $title) use ($rows, $date): ?array {
  foreach ($rows as $r) {
    $in = json_decode($r['inputs'], TRUE) ?: [];
    if ($r['component_id'] === 'sdc.drupal_india_connect.session-overview-card'
      && ($in['date'] ?? '') === $date && ($in['title'] ?? '') === $title) {
      return $r;
    }
  }
  return NULL;
};

$closing = $card_of('Closing ceremony');
if (!$closing) {
  throw new \RuntimeException('No Day 2 Closing ceremony card on /program.');
}
$closing_item = $closing['parent_uuid'];
$timeline = NULL;
foreach ($rows as $r) {
  if ($r['uuid'] === $closing_item) {
    $timeline = $r['parent_uuid'];
  }
}

$component = fn($id) => $etm->getStorage('component')->load($id);
$item_c = $component('sdc.drupal_india_connect.program-timeline-item');
$card_c = $component('sdc.drupal_india_connect.session-overview-card');
$json = fn(array $a) => json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$item_inputs = $json([
  'kind' => 'social',
  'start' => $party['start'],
  'end' => $party['end'],
  'date' => $date,
  'block_end' => TRUE,
]);
$card_inputs = $json([
  'variant' => 'social',
  'track' => 'Evening social',
  'track_icon' => 'confetti',
  'title' => $party['title'],
  'date' => $date,
  'start' => $party['start'],
  'end' => $party['end'],
  'description' => $party['description'],
]);

// Found by the social variant, not the title, so a renamed card is updated
// rather than added again.
$existing = NULL;
foreach ($rows as $r) {
  $in = json_decode($r['inputs'], TRUE) ?: [];
  if ($r['component_id'] === 'sdc.drupal_india_connect.session-overview-card'
    && ($in['date'] ?? '') === $date && ($in['variant'] ?? '') === 'social') {
    $existing = $r;
  }
}
if ($existing) {
  foreach ($rows as &$r) {
    // Re-runs also move the rows to the components' current versions (the
    // social variant was added after the first run).
    if ($r['uuid'] === $existing['uuid']) {
      $r['inputs'] = $card_inputs;
      $r['component_version'] = $card_c->getActiveVersion();
      $r['label'] = "Card - {$party['title']}";
    }
    if ($r['uuid'] === $existing['parent_uuid']) {
      $r['inputs'] = $item_inputs;
      $r['component_version'] = $item_c->getActiveVersion();
      $r['label'] = "Timeline - {$party['start']} {$party['title']}";
    }
  }
  unset($r);
  $out = $rows;
  $action = 'updated';
}
else {
  $uuid_gen = new UuidGen();
  $item_uuid = $uuid_gen->generate();
  $new = [
    [
      'uuid' => $item_uuid,
      'parent_uuid' => $timeline,
      'slot' => 'items',
      'component_id' => $item_c->id(),
      'component_version' => $item_c->getActiveVersion(),
      'inputs' => $item_inputs,
      'label' => "Timeline - {$party['start']} {$party['title']}",
    ],
    [
      'uuid' => $uuid_gen->generate(),
      'parent_uuid' => $item_uuid,
      'slot' => 'content',
      'component_id' => $card_c->id(),
      'component_version' => $card_c->getActiveVersion(),
      'inputs' => $card_inputs,
      'label' => "Card - {$party['title']}",
    ],
  ];
  // Insert straight after the Closing ceremony's card, so the item follows
  // it in the timeline's items slot.
  $out = [];
  foreach ($rows as $r) {
    $out[] = $r;
    if ($r['uuid'] === $closing['uuid']) {
      array_push($out, ...$new);
    }
  }
  $action = 'added';
}

$lost = array_diff($before, array_column($out, 'uuid'));
if ($lost) {
  throw new \RuntimeException('Unexpected row loss: ' . implode(', ', $lost));
}

$page->set('components', $out);
$violations = $page->validate();
foreach ($violations as $v) {
  echo 'VIOLATION ', $v->getPropertyPath(), ': ', strip_tags((string) $v->getMessage()), "\n";
}
if (count($violations)) {
  echo "Not saved.\n";
  return;
}
$page->setNewRevision(TRUE);
$page->setRevisionLogMessage('Program: Day 2 after party.');
$page->save();

$etm->getStorage('canvas_page')->resetCache([$page->id()]);
$saved = $etm->getStorage('canvas_page')->load($page->id());
$after = array_column($saved->get('components')->getValue(), 'uuid');
echo "canvas_page {$saved->id()} (/program): After party $action, ", count($rows), ' rows -> ', count($after), ".\n";
echo array_diff(array_column($out, 'uuid'), $after) ? "  LOST rows on save\n" : "  No rows lost.\n";
echo '  Post-save violations: ', count($saved->validate()), "\n";
