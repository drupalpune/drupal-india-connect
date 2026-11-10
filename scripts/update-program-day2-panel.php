<?php

/**
 * @file
 * Fills in the Day 2 panel card on /program: title, description, and the
 * moderator and panelists as Panel member rows, like the Day 1 panel card
 * (scripts/build-program-day1.php).
 *
 * Run: ddev drush php:script scripts/update-program-day2-panel.php -- apply
 *
 * Only that card and its own Panel member rows change; every other row is
 * kept. Photos come from docs/panel-revamp/img/ (scripts/includes/panel-photo.php).
 * The card is found by its plenary variant and Day 2 date, not by
 * uuid, so this runs against any environment. Safe to re-run.
 *
 * Note: scripts/build-program-page.php rebuild recreates the card as a
 * generic "Panel discussion"; run this again after any such rebuild.
 */

use Drupal\Component\Uuid\Php as UuidGen;

if (($extra[0] ?? '') !== 'apply') {
  echo "Pass `apply` to write: ddev drush php:script scripts/update-program-day2-panel.php -- apply\n";
  return;
}

$panel = [
  'title' => 'What’s Next for Drupal? Shaping the Next Chapter of the Open Web',
  'description' => 'Four perspectives on Drupal’s direction as a platform, a product and an open-source ecosystem.',
  'moderator' => [
    ['Saheel Sikilkar', 'Syngenta', 'https://www.drupal.org/u/saheelsikilkar', 'Saheel-Sikilkar.png'],
  ],
  'panelists' => [
    ['Lauri Timmanee', 'Experience building, Drupal Core', 'https://www.drupal.org/u/lauriii', 'Lauri-Timmanee.jpg'],
    ['Jamie Abrahams', 'Drupal AI', 'https://www.drupal.org/u/yautja_cetanu', 'Jamie-Abrahams.png'],
    ['Alexander Varwijk', 'Product strategy & adoption', 'https://www.drupal.org/u/kingdutch', 'Alexander-Varwijk.png'],
    ['Piyuesh Kumar', 'Agency implementation', 'https://www.drupal.org/u/piyuesh23', 'Piyuesh-Kumar.png'],
  ],
];

$etm = \Drupal::entityTypeManager();
$path = \Drupal::service('path_alias.manager')->getPathByAlias('/program');
if (!preg_match('#^/page/(\d+)$#', $path, $m)) {
  throw new \RuntimeException('No canvas_page at /program.');
}
$page = $etm->getStorage('canvas_page')->load($m[1]);
$rows = $page->get('components')->getValue();
$before = array_column($rows, 'uuid');

$card = NULL;
foreach ($rows as $r) {
  $in = json_decode($r['inputs'], TRUE) ?: [];
  if ($r['component_id'] === 'sdc.drupal_india_connect.session-overview-card'
    && ($in['variant'] ?? '') === 'plenary' && ($in['date'] ?? '') === '2027-01-29'
    && (($in['title'] ?? '') === 'Panel discussion' || ($in['title'] ?? '') === $panel['title'])) {
    $card = $r['uuid'];
  }
}
if (!$card) {
  throw new \RuntimeException('No Day 2 panel card on /program.');
}

// Drop the card's existing Panel member rows (a re-run), keep everything else.
$old_members = [];
foreach ($rows as $r) {
  if ($r['parent_uuid'] === $card) {
    if ($r['component_id'] !== 'sdc.drupal_india_connect.panel-member') {
      throw new \RuntimeException("Unexpected child {$r['component_id']} under the card.");
    }
    $old_members[] = $r['uuid'];
  }
}

$panel_photo = require __DIR__ . '/includes/panel-photo.php';
$component = fn($id) => $etm->getStorage('component')->load($id);
$uuid_gen = new UuidGen();
$out = [];
foreach ($rows as $r) {
  if (in_array($r['uuid'], $old_members, TRUE)) {
    continue;
  }
  if ($r['uuid'] === $card) {
    $in = json_decode($r['inputs'], TRUE);
    $in = [
      'variant' => 'plenary',
      'track' => 'Panel discussion',
      'track_icon' => 'users-three',
      'note' => count($panel['panelists']) . ' panelists · ' . count($panel['moderator']) . ' moderator',
      'title' => $panel['title'],
      'date' => $in['date'],
      'start' => '13:30',
      'end' => '15:00',
      'room' => 'Auditorium',
      // The title links to the panel's own page.
      'url' => ['uri' => 'internal:/conference/whats-next-for-drupal', 'options' => []],
      'description' => $panel['description'],
    ];
    $r['inputs'] = json_encode(array_filter($in), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $r['label'] = 'Card - Day 2 - Panel discussion';
    // The row predates the card's panel slots and note/track_icon props.
    $r['component_version'] = $component('sdc.drupal_india_connect.session-overview-card')->getActiveVersion();
  }
  $out[] = $r;
}

$member = $component('sdc.drupal_india_connect.panel-member');
foreach (['moderator' => 'Moderator', 'panelists' => 'Panelist'] as $slot => $what) {
  foreach ($panel[$slot] as $i => $member_row) {
    [$name, $role, $url] = $member_row;
    $photo = $member_row[3] ?? '';
    $inputs = ['tba' => FALSE, 'name' => $name] + ($role !== '' ? ['role' => $role] : []) + ($url !== '' ? ['url' => $url] : []) + ($photo !== '' ? ['photo' => $panel_photo($photo)] : []);
    $out[] = [
      'uuid' => $uuid_gen->generate(),
      'parent_uuid' => $card,
      'slot' => $slot,
      'component_id' => $member->id(),
      'component_version' => $member->getActiveVersion(),
      'inputs' => json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      'label' => "Panel member - $what" . ($slot === 'panelists' ? ' ' . ($i + 1) : '') . " - $name",
    ];
  }
}

$lost = array_diff($before, array_column($out, 'uuid'));
if (array_diff($lost, $old_members)) {
  throw new \RuntimeException('Unexpected row loss: ' . implode(', ', array_diff($lost, $old_members)));
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
$page->setRevisionLogMessage('Program: Day 2 panel card with moderator and panelists.');
$page->save();

$etm->getStorage('canvas_page')->resetCache([$page->id()]);
$saved = $etm->getStorage('canvas_page')->load($page->id());
$after = array_column($saved->get('components')->getValue(), 'uuid');
echo "canvas_page {$saved->id()} (/program): ", count($rows), ' rows -> ', count($after), ' (', count($old_members), ' old members replaced, ', count($out) - count($rows) + count($old_members), " added).\n";
echo array_diff(array_column($out, 'uuid'), $after) ? "  LOST rows on save\n" : "  No rows lost.\n";
echo '  Post-save violations: ', count($saved->validate()), "\n";
\Drupal::keyValue('canvas.auto_save')->delete("canvas_page:{$saved->id()}:en");
