<?php

/**
 * @file
 * Builds the Day 2 "What's Next for Drupal?" panel canvas_page (issue #131).
 *
 * Run: ddev drush php:script scripts/build-day2-panel-page.php
 *
 * Creates the page unpublished at the alias below; publish it by hand after
 * the programme/marketing review. If a page already has that alias the
 * script stops, because rebuilding replaces every component row and would
 * discard editor changes. Pass `rebuild` to do that deliberately (local only):
 *   ddev drush php:script scripts/build-day2-panel-page.php rebuild
 *
 * Run it once per environment. After that the page is content: if an editor
 * changes its URL, this script no longer finds it and would build a second
 * page.
 *
 * Follows the AI Summit panel page (build-ai-summit-panel-page.php) and uses
 * only existing components. Deploys are code-only, so ship this script in a
 * release and then run it against each environment's database. Back up
 * first. Copy is taken from the
 * issue's reference (drupalasia-day2-panel.html). No start time, room or
 * panellist names are confirmed: the four panellist slots are striped
 * placeholders, and Saheel Sikilkar is shown only as the proposed moderator.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/ai-summit/whats-next-for-drupal';

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

$html = fn(string $value) => ['value' => $value, 'format' => 'canvas_html_block'];

$section = fn(array $overrides = []) => $overrides + [
  'width' => '100%',
  'columns' => '100',
  'mobile_columns' => '1',
  'margin_block_start' => '0',
  'margin_block_end' => '0',
  'padding_block_start' => '64',
  'padding_block_end' => '64',
  'section_header' => TRUE,
  'section_footer' => FALSE,
  'full_width' => TRUE,
  'dark_background' => FALSE,
];

$group = fn(string $direction, string $gap = 'md', array $extra = []) => $extra + [
  'flex_direction' => $direction,
  'flex_gap' => $gap,
  'items_align' => 'start',
  'flex_align' => 'start',
];

$heading = fn(string $text, int $level = 2, bool $dark = FALSE, ?string $size = NULL) => [
  'heading_text' => $text,
  'level' => $level,
  'text_size' => $size ?? ($level === 1 ? 'heading-responsive-5xl' : 'heading-responsive-3xl'),
  'text_color' => $dark ? 'inverted' : 'default',
  'align' => 'left',
];

$text = fn(string $value, string $size = 'normal', bool $dark = FALSE) => [
  'text' => $html($value),
  'text_size' => $size,
  'text_color' => $dark ? 'inverted' : 'default',
];

/**
 * Section header: eyebrow, h2 and an optional intro line, in the header slot.
 */
$header = function (string $section_uuid, string $name, string $eyebrow, string $title, ?string $intro = NULL, bool $dark = FALSE) use ($add, $group, $heading, $text): void {
  $g = $add('sdc.event_horizon.group', "Group - $name header", $group('column', 'sm'), $section_uuid, 'header_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - $eyebrow", ['text' => $eyebrow, 'tone' => $dark ? 'on-dark' : 'accent'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - $title", $heading($title, 2, $dark), $g, 'group_slot');
  if ($intro) {
    $add('sdc.event_horizon.text', "Text - $name intro", $text("<p>$intro</p>", 'text-lg', $dark), $g, 'group_slot');
  }
};

// ---------------------------------------------------------------------------
// 1. Hero.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Hero', $section([
  'columns' => '75-25',
  'section_header' => FALSE,
  'background_color' => 'black',
  'dark_background' => TRUE,
]));
$g = $add('sdc.event_horizon.group', 'Group - Hero', $group('column', 'lg'), $s, 'main_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Day 2 · Panel discussion · 60 minutes', ['text' => 'Day 2 · Panel discussion · 60 minutes', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - What’s next for Drupal?', $heading('What’s next for Drupal?', 1, TRUE), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Shaping the next chapter', $text('<p>Shaping the next chapter of the open web</p>', 'text-2xl', TRUE), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', $text('<p>A forward-looking conversation on Drupal’s direction as a platform, a product, and an open-source ecosystem.</p>', 'text-lg', TRUE), $g, 'group_slot');
$facts = $add('drupal_india_connect.grid', 'Grid - Hero facts', ['columns' => 3, 'gap' => '24'], $g, 'group_slot');
foreach ([
  ['When', 'Friday, 29 January 2027', 'primary'],
  ['Session length', '60 minutes', 'primary'],
  ['Format', 'Moderated panel + audience Q&A', 'secondary'],
] as [$caption, $value, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $caption", [
    'title' => $caption,
    'text' => $value,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'on-dark',
  ], $facts, 'grid_slot');
}

// ---------------------------------------------------------------------------
// 2. The conversation.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - The conversation', $section([
  'columns' => '67-33',
  'section_header' => FALSE,
]));
$g = $add('sdc.event_horizon.group', 'Group - The conversation', $group('column', 'md'), $s, 'main_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - The conversation', ['text' => 'The conversation', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Where is Drupal heading next?', $heading('Where is Drupal heading next?'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Drupal is evolving', $text('<p>Drupal is evolving on several fronts. Core continues to provide an extensible foundation, Drupal CMS is changing how experiences are delivered, emerging site-building approaches are reshaping workflows, and AI is opening up new possibilities.</p>', 'text-lg'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - These changes are connected', $text('<p>These changes are connected. But what do they mean for the people building, adopting and contributing to Drupal? And which decisions will matter most over the next few years?</p>'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Four perspectives', $text('<p>We are bringing four perspectives together for a candid conversation about the choices, opportunities and trade-offs ahead. This is not a sequence of initiative updates or product pitches. The focus is on understanding what Drupal needs to become, what it must preserve, and how the community can help shape its future.</p>'), $g, 'group_slot');
// In a group so the card keeps its own height instead of the column's.
$aside = $add('sdc.event_horizon.group', 'Group - Side note', $group('column', 'md'), $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Expect different viewpoints', [
  'title' => 'Expect different viewpoints, practical questions and a clearer picture of the opportunities ahead for Drupal.',
  'accent' => 'secondary',
], $aside, 'group_slot');

// ---------------------------------------------------------------------------
// 3. Four perspectives.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Four perspectives', $section([
  'columns' => '50-50',
  'background_color' => 'accent',
]));
$header($s, 'Four perspectives', 'What we will explore', 'Four perspectives. One ecosystem.', 'The discussion will connect important parts of Drupal’s evolution, rather than treating each initiative in isolation.');
foreach ([
  ['01', 'Platform', 'Drupal Core & architecture', 'How should the foundation evolve while retaining the stability and flexibility Drupal is known for?', 'primary'],
  ['02', 'Experience', 'Drupal CMS & site building', 'What needs to change to make powerful digital experiences easier to design, build and maintain?', 'primary'],
  ['03', 'Innovation', 'AI & emerging workflows', 'How might AI-enabled tooling and new interfaces change the way teams work with Drupal?', 'primary'],
  ['04', 'Ecosystem', 'Adoption & sustainability', 'How can Drupal remain relevant, accessible and sustainable for organisations and contributors?', 'secondary'],
] as [$marker, $pill, $title, $body, $accent]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'marker' => $marker,
    'pill' => $pill,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => $accent,
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 4. Picture the next Drupal project.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Next Drupal project', $section([
  'columns' => '33-33-33',
  'background_color' => 'black',
  'dark_background' => TRUE,
  'section_footer' => TRUE,
]));
$header($s, 'Next Drupal project', 'From direction to decisions', 'Picture the next Drupal project.', 'A team is choosing how to build a complex digital experience. It wants fast delivery and a clear editing experience, without giving up integration, governance or the ability to extend the platform.', TRUE);
foreach ([
  ['01', 'Choose', 'The platform', 'Why would the team choose Drupal over other approaches?', 'primary'],
  ['02', 'Build', 'The experience', 'How much can site builders do confidently, and when do developers step in?', 'primary'],
  ['03', 'Evolve', 'The ecosystem', 'What keeps the solution adaptable, maintainable and sustainable in five years?', 'secondary'],
] as [$marker, $pill, $title, $body, $accent]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'marker' => $marker,
    'pill' => $pill,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => $accent,
  ], $s, 'main_slot');
}
$add('sdc.event_horizon.text', 'Text - Not separate questions', $text('<p><strong>Those are not separate questions. Together, they define what Drupal needs to deliver next.</strong></p>', 'text-lg', TRUE), $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 5. Questions worth asking.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Questions worth asking', $section([
  'columns' => '33-33-33',
]));
$header($s, 'Questions', 'Questions worth asking', 'Not just what’s coming. What’s changing?');
foreach ([
  ['Where must Drupal simplify?', 'What complexity adds value, and what gets in the way?'],
  ['What belongs in Core?', 'Which investments strengthen the foundation for everyone?'],
  ['How do we measure adoption?', 'What would make Drupal the clear choice for more teams?'],
  ['Who is Drupal being built for?', 'How do builders, editors, product teams and enterprises shape priorities?'],
  ['Where does AI help?', 'Which workflows improve, and where should we remain cautious?'],
  ['What can the community do?', 'What should participants contribute to next?'],
] as $i => [$question, $body]) {
  $add('drupal_india_connect.info-card', 'Info card - Question ' . ($i + 1), [
    'marker' => sprintf('%02d', $i + 1),
    'title' => $question,
    'body' => $html("<p>$body</p>"),
    'accent' => 'primary',
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 6. The format: 60-minute running order.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - The format', $section([
  'background_color' => 'accent',
]));
$header($s, 'The format', 'The format', '60 minutes of conversation', 'Designed as an interactive panel with space for the audience to join the discussion. The sequence may be refined with the moderator and panellists.');
$table = $add('drupal_india_connect.data-table', 'Data table - Running order', [
  'caption' => 'Running order of the 60-minute panel',
  'column_1' => 'Time',
  'column_2' => 'Segment',
  'column_3' => 'What we will discuss',
], $s, 'main_slot');
foreach ([
  ['00–05 min', 'Setting the scene', 'What is changing in Drupal, and why does it matter now?'],
  ['05–18 min', 'The platform and the product', 'Drupal Core, Drupal CMS and architectural priorities.'],
  ['18–33 min', 'The next wave of experience building', 'Site building, AI and changing developer workflows.'],
  ['33–45 min', 'Adoption and the community', 'What Drupal needs to preserve, improve and rethink.'],
  ['45–55 min', 'Your questions', 'Audience Q&A with the panellists.'],
  ['55–60 min', 'What comes next?', 'One prediction and one invitation to the community from each panellist.'],
] as [$time, $segment, $what]) {
  $add('drupal_india_connect.data-table-row', "Row - $time $segment", [
    'cell_1' => $time,
    'cell_2' => $segment,
    'cell_3' => $what,
  ], $table, 'rows');
}

// ---------------------------------------------------------------------------
// 7. Panellists and proposed moderator.
// ---------------------------------------------------------------------------
// Each slot is a striped placeholder until the panellist confirms; replace
// the slot's group with a People Card then. The moderator is proposed only.
$s = $add('sdc.event_horizon.section', 'Section - On the stage', $section([
  'section_footer' => TRUE,
]));
$header($s, 'On the stage', 'On the stage', 'Meet the conversation', 'Four panellists will bring complementary perspectives from across the Drupal ecosystem. The panellist lineup is being curated and will be announced once confirmed.');
$people = $add('drupal_india_connect.grid', 'Grid - Panellists', ['columns' => 4, 'gap' => '24', 'tablet_columns' => '2'], $s, 'main_slot');
foreach (['01', '02', '03', '04'] as $n) {
  $slot = $add('sdc.event_horizon.group', "Group - Panellist $n", $group('column', 'sm'), $people, 'grid_slot');
  $add('drupal_india_connect.media-placeholder', "Placeholder - Panellist $n portrait", ['caption' => 'Portrait to follow', 'ratio' => '4:3'], $slot, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - Panellist $n", $heading("Panellist $n", 3, FALSE, 'heading-responsive-lg'), $slot, 'group_slot');
  $add('sdc.event_horizon.text', "Text - Panellist $n to be announced", $text('<p>To be announced</p>', 'text-sm'), $slot, 'group_slot');
}
$foot = $add('sdc.event_horizon.group', 'Group - Moderator', $group('column', 'md'), $s, 'footer_slot');
$add('drupal_india_connect.info-card', 'Info card - Saheel Sikilkar (proposed moderator)', [
  'pill' => 'Proposed moderator · not confirmed',
  'title' => 'Saheel Sikilkar',
  'body' => $html('<p>Proposed to guide the conversation and audience Q&amp;A. Moderator participation is subject to confirmation.</p>'),
  'accent' => 'secondary',
], $foot, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Placeholder note', $text('<p>Speaker names and affiliations will be added after participation is confirmed. The four panel positions shown above are placeholders, not confirmed bookings.</p>', 'text-sm'), $foot, 'group_slot');

// ---------------------------------------------------------------------------
// 8. Closing. No button: registration is not promised here.
// ---------------------------------------------------------------------------
$add('drupal_india_connect.closing-cta', 'Closing CTA - The next chapter', [
  'eyebrow' => 'Join the discussion',
  'heading' => 'The next chapter will be shaped together.',
  'text' => 'Leave with a sharper understanding of Drupal’s strategic choices, the trade-offs that matter, and where your own work or contribution can have an impact. Whether you build Drupal, use it to deliver digital experiences, or contribute to its ecosystem, this discussion is about the choices that shape the future of the open web.',
  'stub_title' => 'DrupalAsia Connect India 2027',
  'detail_1_label' => 'Day',
  'detail_1_value' => 'Day 2',
  'detail_2_label' => 'Date',
  'detail_2_value' => 'Friday, 29 January 2027',
  'detail_3_label' => 'Session length',
  'detail_3_value' => '60 minutes',
  'detail_4_label' => 'Venue',
  'detail_4_value' => 'IIT Bombay, Mumbai',
]);

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
    'title' => 'What’s Next for Drupal? Shaping the Next Chapter of the Open Web',
    'description' => 'A Day 2 panel discussion at DrupalAsia Connect India 2027, Friday 29 January 2027: Drupal Core, Drupal CMS, AI-enabled workflows and adoption, in 60 minutes with audience Q&A.',
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
$page->setRevisionLogMessage('Build Day 2 "What’s Next for Drupal?" panel page (issue #131).');
$page->save();

// Re-load and confirm nothing was dropped.
$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$before = array_column($rows, 'uuid');
$after = array_column($saved->get('components')->getValue(), 'uuid');
$lost = array_diff($before, $after);
echo 'Saved canvas_page ', $saved->id(), ' at ', $alias, ': ', count($after), ' rows (', count($before), " built).\n";
echo $lost ? 'LOST ' . count($lost) . " rows\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
