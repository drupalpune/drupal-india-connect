<?php

/**
 * @file
 * Fills the Day 1 (AI Summit) tab of the Program page at /program.
 *
 * Issue #172. Replaces whatever is in the Program days component's Day 1
 * slot (the "To be announced soon" callout) with the AI Summit program:
 * a preview note, the seven speaker sessions and the panel discussion.
 * Day 2 and Day 3 are not touched.
 *
 *   ddev drush php:script scripts/build-program-day1.php -- dry-run
 *   ddev drush php:script scripts/build-program-day1.php
 *
 * Once Day 1 holds sessions the script stops, so it cannot overwrite editor
 * changes by accident. To apply edits made to the content below, pass
 * `rebuild`: that replaces the Day 1 slot again (and only that slot).
 *
 * Needs the theme release that adds track-badge, panel-member, the new
 * session-overview-card props, grid's 1-column option and eyebrow's muted
 * tone. Deploys are code-only, so run this against each environment's
 * database after that release ships. Back up the database first.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/program';
$date = '2027-01-28';
$dry_run = in_array('dry-run', $extra ?? [], TRUE);
$rebuild = in_array('rebuild', $extra ?? [], TRUE);

$link = fn(string $uri) => ['uri' => $uri, 'options' => []];
$html = fn(array $paragraphs) => [
  'value' => implode('', array_map(fn($p) => '<p>' . htmlspecialchars($p, ENT_QUOTES) . '</p>', $paragraphs)),
  'format' => 'canvas_html_block',
];

// ---------------------------------------------------------------------------
// Content. Speaker names, roles and LinkedIn profiles are as on /ai-summit.
// For a session with no confirmed title, leave 'title' NULL.
// ---------------------------------------------------------------------------
$sessions = [
  [
    'track' => 'Martech, Marketing and Customer Experience',
    'key' => 'martech',
    'icon' => 'megaphone',
    'title' => 'The Great Rewiring: When Intelligence Becomes Abundant, What Makes You Valuable?',
    'description' => 'AI is transforming much more than how we create content or automate marketing. It is fundamentally changing how businesses understand customers, build experiences, generate demand, sell, and bring creative ideas to life.',
    'summary' => [
      'As intelligence becomes increasingly accessible through LLMs, specialized models, and autonomous agents, traditional boundaries between marketing, sales, technology, and creativity are beginning to dissolve.',
      "This session explores how customer engagement, go-to-market strategies, and creative processes are evolving—and why the real opportunity isn't simply doing existing work faster, but reimagining what's possible.",
      "Drawing from real-world experiences in customer intelligence, personalization, and building AI-powered systems, I'll explore how professionals and businesses can adapt, develop new capabilities, and thrive in this rapidly changing landscape.",
      "The central message: Don't use AI merely to do yesterday's work faster. Use it to discover what you were never able to do before.",
    ],
    'speaker' => ['Vidhatanand V.', 'Founder & CEO, Fragmatic', 'https://www.linkedin.com/in/vidhatanand/'],
  ],
  [
    'track' => 'Enterprise AI and Digital Transformation',
    'key' => 'enterprise-ai',
    'icon' => 'buildings',
    'speaker' => ['Muktesh Kandpal', 'Founder and CEO, Varity AI', 'https://www.linkedin.com/in/mukteshkandpal/'],
  ],
  [
    'track' => 'AI in Infrastructure and MLOps',
    'key' => 'ai-infrastructure',
    'icon' => 'cpu',
    'speaker' => ['Lakshmi Narasimhan Parthasarathy', 'Principal Site Reliability Engineer', 'https://www.linkedin.com/in/lakshminp/'],
  ],
  [
    'track' => 'AI Education and Social Impact',
    'key' => 'ai-education',
    'icon' => 'graduation-cap',
    'speaker' => ['Dr. Abhijat Vichare', 'Consultant', 'https://www.linkedin.com/in/abhijat-vichare-8288222/'],
  ],
  [
    'track' => 'AI in Government and Public Sector',
    'key' => 'ai-government',
    'icon' => 'bank',
    'speaker' => ['David Joseph Menezes', 'Director, Programs, People+ai', 'https://www.linkedin.com/in/david-menezes/'],
  ],
  [
    'track' => 'CMS and Digital Experience',
    'key' => 'cms-dx',
    'icon' => 'browsers',
    'speaker' => ['Prateek Jain', 'Director of Digital Solutions & AI Strategy, Axelerant', 'https://www.linkedin.com/in/prateekjain87/'],
  ],
  [
    'track' => 'Open Source AI',
    'key' => 'open-source-ai',
    'icon' => 'code',
    'speaker' => ['Prof. Ganesh Ramakrishnan', 'Founding Director, BharatGen & Professor, IIT Bombay', 'https://www.linkedin.com/in/ganesh-ramakrishnan-ba6164b'],
  ],
];

// Panel: [name, role, profile URL]; an empty name shows "To be announced".
$panel = [
  'title' => 'Building Intelligent Public Infrastructure: Open Source, Drupal, and AI',
  'description' => 'How open source, Drupal and AI come together to build public digital infrastructure.',
  'moderator' => [['', '', '']],
  'panelists' => array_fill(0, 5, ['', '', '']),
];

// ---------------------------------------------------------------------------
// Load the page and find the Day 1 slot.
// ---------------------------------------------------------------------------
$etm = \Drupal::entityTypeManager();
$storage = $etm->getStorage('canvas_page');
$path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
if (!preg_match('#^/page/(\d+)$#', $path, $m) || !($page = $storage->load($m[1]))) {
  throw new \RuntimeException("No canvas_page at $alias.");
}

$drafts = array_filter(
  array_keys(\Drupal::keyValue('canvas.auto_save')->getAll()),
  fn($key) => str_starts_with($key, "canvas_page:{$page->id()}:"),
);
if ($drafts) {
  echo "canvas_page {$page->id()} has unpublished Canvas changes (" . implode(', ', $drafts) . "). Not touching it.\n";
  echo "Publish or discard them in the Canvas editor and close its tabs, then run this again.\n";
  return;
}

$rows = array_values($page->get('components')->getValue());
$before = array_column($rows, 'uuid');

$days = array_values(array_filter($rows, fn($r) => $r['component_id'] === 'sdc.drupal_india_connect.program-days'));
if (count($days) !== 1) {
  throw new \RuntimeException('Expected one Program days component, found ' . count($days) . '.');
}
$days_uuid = $days[0]['uuid'];

// Everything in the Day 1 slot, resolved through parent_uuid.
$subtree = [];
$queue = [];
foreach ($rows as $r) {
  if (($r['parent_uuid'] ?? NULL) === $days_uuid && ($r['slot'] ?? NULL) === 'day_1') {
    $subtree[$r['uuid']] = $r;
    $queue[] = $r['uuid'];
  }
}
while ($queue) {
  $parent = array_shift($queue);
  foreach ($rows as $r) {
    if (($r['parent_uuid'] ?? NULL) === $parent) {
      $subtree[$r['uuid']] = $r;
      $queue[] = $r['uuid'];
    }
  }
}

$has_sessions = (bool) array_filter($subtree, fn($r) => $r['component_id'] === 'sdc.drupal_india_connect.session-overview-card');
if ($has_sessions && !$rebuild) {
  echo "Day 1 already holds the program. Not touching it.\n";
  echo "Pass 'rebuild' to replace the Day 1 slot (and only that) with the content in this script.\n";
  return;
}

// ---------------------------------------------------------------------------
// Build the new Day 1 rows.
// ---------------------------------------------------------------------------
$component_storage = $etm->getStorage('component');
$uuid_gen = new UuidGen();
$new = [];
$add = function (string $component, string $label, array $inputs, string $parent, string $slot) use (&$new, $component_storage, $uuid_gen): string {
  $id = "sdc.$component";
  $entity = $component_storage->load($id);
  if (!$entity || !$entity->status()) {
    throw new \RuntimeException("Component $id missing or disabled. Is the theme release deployed?");
  }
  $uuid = $uuid_gen->generate();
  $new[] = [
    'uuid' => $uuid,
    'parent_uuid' => $parent,
    'slot' => $slot,
    'component_id' => $id,
    'component_version' => $entity->getActiveVersion(),
    'inputs' => json_encode($inputs ?: new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'label' => $label,
  ];
  return $uuid;
};

$stack = $add('drupal_india_connect.grid', 'Grid - Day 1 program', ['columns' => 1, 'gap' => '24'], $days_uuid, 'day_1');

$add('drupal_india_connect.callout', 'Callout - Day 1 program preview', [
  'icon' => 'brain',
  'heading' => 'Program preview',
  'text' => 'These are the confirmed AI Summit speakers and their tracks. The order below is not a running order: we will add times, rooms and the remaining session titles here once they are confirmed.',
  'cta_label' => 'About the AI Summit',
  'cta_url' => $link('internal:/ai-summit'),
  'style' => 'dashed',
], $stack, 'grid_slot');

$add('drupal_india_connect.eyebrow', 'Eyebrow - Speaker sessions', ['text' => 'Speaker sessions', 'tone' => 'muted'], $stack, 'grid_slot');

foreach ($sessions as $s) {
  [$name, $role, $profile] = $s['speaker'];
  $inputs = [
    'variant' => 'session',
    'track' => $s['track'],
    'track_key' => $s['key'],
    'track_icon' => $s['icon'],
    'title' => $s['title'] ?? 'Title to be announced',
    'title_tbc' => empty($s['title']),
    'date' => $date,
    'speaker_1_name' => $name,
    'speaker_1_role' => $role,
    'speaker_1_url' => $link($profile),
  ];
  if (!empty($s['description'])) {
    $inputs['description'] = $s['description'];
  }
  if (!empty($s['summary'])) {
    $inputs['summary'] = $html($s['summary']);
  }
  $add('drupal_india_connect.session-overview-card', "Card - Day 1 - $name", $inputs, $stack, 'grid_slot');
}

$panel_uuid = $add('drupal_india_connect.session-overview-card', 'Card - Day 1 - Panel discussion', [
  'variant' => 'plenary',
  'track' => 'Panel discussion',
  'track_icon' => 'users-three',
  'note' => count($panel['panelists']) . ' panelists · ' . count($panel['moderator']) . ' moderator',
  'title' => $panel['title'],
  'date' => $date,
  'description' => $panel['description'],
], $stack, 'grid_slot');

foreach (['moderator' => 'Moderator', 'panelists' => 'Panelist'] as $slot => $what) {
  foreach ($panel[$slot] as $i => [$name, $role, $profile]) {
    // Canvas rejects a row with no inputs at all, so a place still to be
    // filled says so explicitly.
    $inputs = ['tba' => TRUE];
    if ($name !== '') {
      $inputs = ['tba' => FALSE, 'name' => $name] + ($role !== '' ? ['role' => $role] : []) + ($profile !== '' ? ['url' => $link($profile)] : []);
    }
    $label = "Panel member - $what" . ($slot === 'panelists' ? ' ' . ($i + 1) : '') . ' - ' . ($name !== '' ? $name : 'TBA');
    $add('drupal_india_connect.panel-member', $label, $inputs, $panel_uuid, $slot);
  }
}

// ---------------------------------------------------------------------------
// Swap the Day 1 slot, set the Day 1 detail line, validate, save.
// ---------------------------------------------------------------------------
$out = [];
foreach ($rows as $r) {
  if (isset($subtree[$r['uuid']])) {
    continue;
  }
  if ($r['uuid'] === $days_uuid) {
    $inputs = is_string($r['inputs']) ? json_decode($r['inputs'], TRUE) : (array) $r['inputs'];
    $inputs['day_1_meta'] = 'AI Summit · ' . count($sessions) . ' speaker sessions and 1 panel';
    $r['inputs'] = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  $out[] = $r;
}
array_push($out, ...$new);

// Guard: only the old Day 1 rows may disappear.
$lost = array_diff($before, array_column($out, 'uuid'));
$unexpected = array_diff($lost, array_keys($subtree));
if ($unexpected || count($lost) !== count($subtree)) {
  throw new \RuntimeException('Unexpected row loss: ' . implode(', ', $unexpected));
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

$removed = implode(' | ', array_column($subtree, 'label')) ?: 'nothing';
if ($dry_run) {
  echo "DRY RUN, not saved. Would remove: $removed\n";
  echo 'Would add ' . count($new) . ' rows; ' . count($rows) . ' -> ' . count($out) . " rows; validation passed.\n";
  return;
}

$page->setNewRevision(TRUE);
$page->setRevisionLogMessage('Day 1 (AI Summit) program, issue #172.');
$page->save();

$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$after = array_column($saved->get('components')->getValue(), 'uuid');
echo "Removed: $removed\n";
echo 'Added ' . count($new) . ' rows; ' . count($rows) . ' -> ' . count($after) . ' rows; missing after save: ' . count(array_diff(array_column($out, 'uuid'), $after)) . '; violations: ' . count($saved->validate()) . "\n";
