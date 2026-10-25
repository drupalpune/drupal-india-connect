<?php

/**
 * @file
 * Builds the Program (session schedule) canvas_page at /program.
 *
 * Run: ddev drush php:script scripts/build-program-page.php
 *
 * Creates the page unpublished; publish it by hand after review. If a page
 * already has the alias the script stops, because rebuilding replaces every
 * component row and would discard editor changes. Pass `rebuild` to do that
 * deliberately:
 *   ddev drush php:script scripts/build-program-page.php rebuild
 *
 * Day 2's times and rooms come from the program team's session plan sheet
 * ("Session plan for DrupalAsia Connect India 2027", Program Plan tab); each
 * session's title, track, synopsis and speakers come from its session node,
 * matched by title, so re-run with `rebuild` after sessions are edited.
 * Node ids differ between environments, which is why titles are the key.
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database after the release carrying the program-* and
 * session-overview-card components has shipped. Back up the database first.
 */

use Drupal\Component\Uuid\Php as UuidGen;
use Drupal\node\NodeInterface;

$alias = '/program';
$date = '2027-01-29';

$etm = \Drupal::entityTypeManager();
$component_storage = $etm->getStorage('component');
$uuid_gen = new UuidGen();
$rows = [];

/**
 * Adds a row and returns its uuid.
 */
$add = function (string $component, string $label, array $inputs, ?string $parent = NULL, ?string $slot = NULL) use (&$rows, $component_storage, $uuid_gen): string {
  $id = str_starts_with($component, 'sdc.') ? $component : "sdc.$component";
  $entity = $component_storage->load($id);
  if (!$entity || !$entity->status()) {
    throw new \RuntimeException("Component $id missing or disabled");
  }
  $uuid = $uuid_gen->generate();
  $rows[] = [
    'uuid' => $uuid,
    'parent_uuid' => $parent,
    'slot' => $parent ? $slot : NULL,
    'component_id' => $id,
    'component_version' => $entity->getActiveVersion(),
    'inputs' => json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'label' => $label,
  ];
  return $uuid;
};

$link = fn(string $uri) => ['uri' => $uri, 'options' => []];

// ---------------------------------------------------------------------------
// Day 2 (Friday 29 January), from the sheet's merged cells. Rooms 1-3 and
// the auditorium run 45-minute talks; Room 4 runs two 20-minute talks in
// the same block. Each session: [room, start, end, title as on the node].
// The sheet's Room 5/6, BoF and Open Stage columns are placeholders and are
// left out.
// ---------------------------------------------------------------------------
$day2 = [
  ['08:00', '09:00', 'break', 'Registration'],
  ['09:00', '10:15', 'plenary', 'Keynote'],
  ['10:15', '10:30', 'break', 'Group photo'],
  ['10:30', '11:15', 'sessions', [
    ['Auditorium', '10:30', '11:15', 'Drupal CMS + Canvas, one year in'],
    ['Room 1', '10:30', '11:15', "PHP Fibers and Drupal: From Core's Biggest Performance Leap to the AI Layer"],
    ['Room 2', '10:30', '11:15', 'Blueprint Before Code: Spec-Driven Development in Drupal'],
    ['Room 3', '10:30', '11:15', 'Engineering for the Agentic Web When 50% of Your Traffic is Robots'],
    ['Room 4', '10:30', '10:50', "No Engineers Were Harmed: A Marketer's Guide to Automating SEO Grunt Work"],
    ['Room 4', '10:55', '11:15', '12 Years, 12 Clearings, Zero Drama. How Drupal carried UCAS through UK Clearing Day, for the twelfth year running'],
  ]],
  ['11:15', '11:30', 'break', 'Short break'],
  ['11:30', '12:15', 'sessions', [
    ['Auditorium', '11:30', '12:15', 'Build your website by chatting with it: Building Atelier on Drupal and FlowDrop'],
    ['Room 1', '11:30', '12:15', 'Accelerating Client Delivery with Drupal Canvas: A Practical Modernization Demo'],
    ['Room 2', '11:30', '12:15', 'Drupal and DPDPA (Digital Personal Data Protection Act)'],
    ['Room 3', '11:30', '12:15', 'Drupal Has the Answers, Now Put Them Anywhere'],
    ['Room 4', '11:30', '11:50', 'HTMX Without Breaking Screen Readers: Accessibility Patterns for Drupal 11.3'],
    ['Room 4', '11:55', '12:15', 'What We Learned Using AI to Write Proposals at Volume'],
  ]],
  ['12:15', '13:30', 'break', 'Lunch'],
  ['13:30', '15:00', 'plenary', 'Panel discussion'],
  ['15:00', '15:45', 'sessions', [
    ['Auditorium', '15:00', '15:45', 'Drupal Canvas and Agentic Content Management'],
    ['Room 1', '15:00', '15:45', 'The AI-Ready Drupal Agency: From Implementation Partner to Innovation Partner'],
    ['Room 2', '15:00', '15:45', 'One Pantry, Four Ways to Cook: Choosing Your Drupal Frontend in the Canvas Era'],
    ['Room 3', '15:00', '15:45', 'From Prompt to Policy: Governing AI with Drupal Context Control Center'],
    ['Room 4', '15:00', '15:20', 'Letting AI agents write to a live Drupal site: the gates that matter'],
    ['Room 4', '15:25', '15:45', 'From Prompt to Interface: Building Adaptive CMS Experiences with Drupal AI and A2UI'],
  ]],
  ['15:45', '16:15', 'break', 'Coffee break'],
  ['16:15', '17:00', 'sessions', [
    ['Auditorium', '16:15', '17:00', 'I seem to be known as an AI skeptic. So, why did I sign up Cambridge University Press and Assessment as a Drupal AI Partner?'],
    ['Room 1', '16:15', '17:00', "Beyond the Lighthouse Score: What Accessibility Automation Can't See"],
    ['Room 2', '16:15', '17:00', "Brooks' Law Meets Agentic AI: Can More Agents Actually Slow You Down?"],
    ['Room 3', '16:15', '17:00', 'Building Next-Generation Drupal Components with SDC, Canvas & AI'],
    ['Room 4', '16:15', '16:35', 'Stuck in the Queue: How to Contribute Without Hitting a Wall'],
    ['Room 4', '16:40', '17:00', 'Building an Open Source AI Community: From Code to Collective Impact'],
  ]],
  ['17:00', '17:15', 'break', 'Short break'],
  ['17:15', '18:00', 'sessions', [
    ['Auditorium', '17:15', '18:00', 'AI Observability in Drupal'],
    ['Room 1', '17:15', '18:00', 'Headless Drupal Canvas in the Real World: What We Tried, What We Dropped, and What Worked'],
    ['Room 2', '17:15', '18:00', 'Moving Issue Queues to GitLab for Drupal Projects'],
    ['Room 3', '17:15', '18:00', 'Which MCP Server for Drupal? A Field Guide to Choices, Trade-offs and Gaps'],
    ['Room 4', '17:15', '17:35', "Let's stop saying \"If I can do it, so can you\""],
  ]],
  ['18:00', '18:30', 'plenary', 'Closing ceremony'],
];

// Speakers the sheet names for sessions whose node has no speaker account.
$sheet_speakers = [
  'Blueprint Before Code: Spec-Driven Development in Drupal' => ['Vighnesh Sadagopal'],
  'Build your website by chatting with it: Building Atelier on Drupal and FlowDrop' => ['Shibin'],
  'Drupal Has the Answers, Now Put Them Anywhere' => ['Abhisek Mazumdar'],
  'Letting AI agents write to a live Drupal site: the gates that matter' => ['Souvik Pal'],
];

// Display names as the sheet writes them, where the account holds a username.
$name_fix = [
  'rachel_norfolk' => 'Rachel Norfolk',
  'swarad' => 'Swarad',
  'govindm' => 'Govindm',
  'gaurav' => 'Gaurav',
  'JEET LAL PATEL' => 'Jeet Lal Patel',
];

// Session category term -> track colour (.dac-track--* in global.css).
$track_keys = [
  'Drupal CMS' => 'drupal-cms',
  'Development, AI and Agentic Architecture' => 'development',
  'Agency, Business & Marketing' => 'agency',
  'Success Stories & Innovation' => 'success',
  'Digital Sovereignty & Open Web' => 'sovereignty',
  'Community Health' => 'community',
  'User Experience, Accessibility, & Design' => 'ux',
];

// ---------------------------------------------------------------------------
// Resolve every session by title before building anything.
// ---------------------------------------------------------------------------
$normalise = fn(string $title) => mb_strtolower(preg_replace('/\s+/u', ' ', trim(str_replace(['’', '“', '”'], ["'", '"', '"'], $title))));
$by_title = [];
foreach ($etm->getStorage('node')->loadByProperties(['type' => 'session', 'status' => 1]) as $node) {
  $by_title[$normalise($node->label())][] = $node;
}

$sessions = [];
$problems = [];
foreach ($day2 as [, , $kind, $items]) {
  if ($kind !== 'sessions') {
    continue;
  }
  foreach ($items as [, , , $title]) {
    $found = $by_title[$normalise($title)] ?? [];
    if (count($found) !== 1) {
      $problems[] = (count($found) ? 'Several' : 'No') . " published session titled \"$title\"";
      continue;
    }
    $sessions[$title] = reset($found);
  }
}
if ($problems) {
  echo implode("\n", $problems), "\nNothing built.\n";
  return;
}

/**
 * Card inputs for one session node.
 */
$session_inputs = function (NodeInterface $node, string $sheet_title, string $room, string $start, string $end) use ($date, $link, $sheet_speakers, $name_fix, $track_keys): array {
  $track = $node->get('field_session_category')->entity?->label() ?? '';
  if (!isset($track_keys[$track])) {
    throw new \RuntimeException("No track colour for \"$track\" ({$node->label()})");
  }

  // Synopsis, or the start of the description, cut at a word near 240.
  $text = trim((string) $node->get('field_synopsis')->value)
    ?: trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $node->get('field_description')->value))));
  if (mb_strlen($text) > 240) {
    $text = rtrim(preg_replace('/\s+\S*$/u', '', mb_substr($text, 0, 240)), ',;:.') . '…';
  }

  $speakers = [];
  foreach ($node->get('field_speakers')->referencedEntities() as $account) {
    $name = trim((string) ($account->get('field_display_name')->value ?: $account->getDisplayName()));
    $username = trim((string) $account->get('field_drupal_org_username')->value);
    $url = match (TRUE) {
      $username === '' => NULL,
      str_starts_with($username, 'http') => $username,
      default => 'https://www.drupal.org/u/' . strtolower(str_replace(' ', '-', $username)),
    };
    $speakers[] = [$name_fix[$name] ?? $name, $url];
  }
  if (!$speakers) {
    $speakers = array_map(fn($name) => [$name, NULL], $sheet_speakers[$sheet_title] ?? []);
  }

  $inputs = [
    'variant' => 'session',
    'track' => $track,
    'track_key' => $track_keys[$track],
    'title' => trim($node->label()),
    'url' => $link('entity:node/' . $node->id()),
    'room' => $room,
    'date' => $date,
    'start' => $start,
    'end' => $end,
    'description' => $text,
  ];
  foreach (array_slice($speakers, 0, 3) as $i => [$name, $url]) {
    $n = $i + 1;
    $inputs["speaker_{$n}_name"] = $name;
    if ($url) {
      $inputs["speaker_{$n}_url"] = $link($url);
    }
  }
  return $inputs;
};

// ---------------------------------------------------------------------------
// Page: one section holding the heading and the day tabs.
// ---------------------------------------------------------------------------
$section = $add('sdc.event_horizon.section', 'Section - Session schedule', [
  'width' => '100%',
  'columns' => '100',
  'mobile_columns' => '1',
  'margin_block_start' => '0',
  'margin_block_end' => '0',
  'padding_block_start' => '32',
  'padding_block_end' => '64',
  'section_header' => FALSE,
  'section_footer' => FALSE,
  'full_width' => TRUE,
  'dark_background' => FALSE,
]);

$add('sdc.event_horizon.heading', 'Heading - Session schedule', [
  'heading_text' => 'DrupalAsia Connect India 2027 Session Schedule',
  'level' => 1,
  'text_size' => 'heading-responsive-4xl',
  'text_color' => 'default',
  'align' => 'center',
], $section, 'main_slot');

$session_count = 0;
foreach ($day2 as [, , $kind, $items]) {
  $session_count += $kind === 'sessions' ? count($items) : 0;
}

$days = $add('drupal_india_connect.program-days', 'Program days', [
  'default_day' => '2',
  'day_1_label' => 'Day 1',
  'day_1_date' => 'Thursday 28 January',
  'day_1_date_short' => 'Thu 28 Jan',
  'day_1_heading' => 'Thursday, 28 January',
  'day_2_label' => 'Day 2',
  'day_2_date' => 'Friday 29 January',
  'day_2_date_short' => 'Fri 29 Jan',
  'day_2_heading' => 'Friday, 29 January',
  'day_2_meta' => "$session_count sessions across five rooms · 08:00–18:30",
  'day_3_label' => 'Day 3',
  'day_3_date' => 'Saturday 30 January',
  'day_3_date_short' => 'Sat 30 Jan',
  'day_3_heading' => 'Saturday, 30 January',
], $section, 'main_slot');

// Days 1 and 3: to be announced.
foreach ([1, 3] as $n) {
  $add('drupal_india_connect.callout', "Callout - Day $n to be announced", [
    'icon' => 'calendar',
    'heading' => 'To be announced soon',
    'text' => "We are finalising the Day $n program and will publish it here first.",
    'cta_label' => 'See the Day 2 program',
    'cta_url' => $link('internal:#day-2'),
    'style' => 'solid',
  ], $days, "day_$n");
}

// Day 2: the timeline.
$timeline = $add('drupal_india_connect.program-timeline', 'Program timeline - Day 2', [], $days, 'day_2');

foreach ($day2 as [$start, $end, $kind, $items]) {
  if ($kind === 'break') {
    $add('drupal_india_connect.program-timeline-item', "Timeline - $start $items", [
      'kind' => 'break',
      'start' => $start,
      'end' => $end,
      'date' => $date,
      'label' => $items,
      'block_end' => TRUE,
    ], $timeline, 'items');
    continue;
  }

  if ($kind === 'plenary') {
    $item = $add('drupal_india_connect.program-timeline-item', "Timeline - $start $items", [
      'kind' => 'plenary',
      'start' => $start,
      'end' => $end,
      'date' => $date,
      'block_end' => TRUE,
    ], $timeline, 'items');
    $add('drupal_india_connect.session-overview-card', "Card - $items", [
      'variant' => 'plenary',
      'track' => 'All attendees',
      'title' => $items,
      'date' => $date,
      'start' => $start,
      'end' => $end,
      'description' => 'Speakers and room to be announced.',
    ], $item, 'content');
    continue;
  }

  foreach ($items as $i => [$room, $s, $e, $title]) {
    $inputs = $session_inputs($sessions[$title], $title, $room, $s, $e);
    $short = mb_strimwidth($inputs['title'], 0, 60, '…');
    $item = $add('drupal_india_connect.program-timeline-item', "Timeline - $s $room", [
      'kind' => 'session',
      'start' => $s,
      'end' => $e,
      'date' => $date,
      'track_key' => $inputs['track_key'],
      'block_end' => $i === count($items) - 1,
    ], $timeline, 'items');
    $add('drupal_india_connect.session-overview-card', "Card - $short", $inputs, $item, 'content');
  }
}

// ---------------------------------------------------------------------------
// Save, with the guards from CLAUDE.md.
// ---------------------------------------------------------------------------
$storage = $etm->getStorage('canvas_page');
$existing = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
$page = NULL;
if (preg_match('#^/page/(\d+)$#', $existing, $m)) {
  $page = $storage->load($m[1]);
  if (($extra[0] ?? '') !== 'rebuild') {
    echo "canvas_page {$page->id()} already has the alias $alias. Not touching it.\n";
    echo "Pass 'rebuild' to replace its components (discards any editor changes).\n";
    return;
  }
}
if (!$page) {
  $page = $storage->create([
    'title' => 'Session Schedule',
    'description' => 'The DrupalAsia Connect India 2027 session schedule: every talk on Day 2, 29 January 2027, with track, room, time and speakers. Days 1 and 3 to be announced.',
    'path' => ['alias' => $alias],
    'status' => FALSE,
    'owner' => 1,
  ]);
}

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
$page->setRevisionLogMessage('Build the session schedule page.');
$page->save();

// Re-load and confirm nothing was dropped.
$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$before = array_column($rows, 'uuid');
$after = array_column($saved->get('components')->getValue(), 'uuid');
$lost = array_diff($before, $after);
echo 'Saved canvas_page ', $saved->id(), ' at ', $alias, ': ', count($after), ' rows (', count($before), " built), $session_count sessions.\n";
echo $lost ? 'LOST ' . count($lost) . " rows\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
