<?php

/**
 * @file
 * Builds the AI Summit panel discussion canvas_page (issue #130).
 *
 * Run: ddev drush php:script scripts/build-ai-summit-panel-page.php
 *
 * Creates the page unpublished at the alias below; publish it by hand after
 * Programme/PMO sign-off. If a page already has that alias the script stops,
 * because rebuilding replaces every component row and would discard editor
 * changes. Pass `rebuild` to do that deliberately (local only):
 *   ddev drush php:script scripts/build-ai-summit-panel-page.php rebuild
 *
 * Run it once per environment. After that the page is content: if an editor
 * changes its URL, this script no longer finds it and would build a second
 * page.
 *
 * Uses only components already on /ai-summit. Deploys are code-only, so ship
 * this script in a release and then run it against each environment's
 * database. Back up first. Copy is
 * taken from the issue's reference (panel-discussion-polished.html). The
 * session time and panelists are not confirmed, so the page says "To be
 * announced" rather than inventing them; editors replace the dashed callout
 * with People Cards as panelists confirm.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/ai-summit/building-intelligent-public-infrastructure';

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

$heading = fn(string $text, int $level = 2, bool $dark = FALSE) => [
  'heading_text' => $text,
  'level' => $level,
  'text_size' => $level === 1 ? 'heading-responsive-5xl' : 'heading-responsive-3xl',
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
$add('drupal_india_connect.eyebrow', 'Eyebrow - AI Summit · Panel discussion', ['text' => 'AI Summit · Panel discussion', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Building intelligent public infrastructure', $heading('Building intelligent public infrastructure', 1, TRUE), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', $text('<p>Open source, Drupal, and AI. How can they work together to make public services easier to find, understand, and use, without losing trust or accountability?</p>', 'text-lg', TRUE), $g, 'group_slot');
$facts = $add('drupal_india_connect.grid', 'Grid - Hero facts', ['columns' => 4, 'gap' => '24'], $g, 'group_slot');
foreach ([
  ['When', '28 January 2027', 'primary'],
  ['Where', 'IIT Bombay, Mumbai', 'primary'],
  ['Part of', 'DrupalAsia Connect India 2027 · AI Summit', 'primary'],
  ['Session time', 'To be announced', 'secondary'],
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
$add('sdc.event_horizon.heading', 'Heading - From digital rails to real access', $heading('From digital rails to real access'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Digital Public Infrastructure', $text('<p>Digital Public Infrastructure creates shared foundations for identity, payments, credentials, data exchange and public services. But the infrastructure itself does not guarantee that someone can discover a service, understand whether they qualify, or complete the next step.</p>', 'text-lg'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - This panel explores', $text('<p>This panel explores the space between public infrastructure and people: trustworthy information, accessible experiences, multilingual services, and the responsible use of AI.</p>'), $g, 'group_slot');
// Not a blockquote: its quote mark is drawn over the first words.
$aside = $add('sdc.event_horizon.group', 'Group - Side note', $group('column', 'md'), $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - What becomes possible', [
  'title' => 'What becomes possible when public systems, mature open-source platforms, and AI are designed to work together?',
  'accent' => 'secondary',
], $aside, 'group_slot');

// ---------------------------------------------------------------------------
// 3. Three layers, one public purpose.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Three layers', $section([
  'columns' => '33-33-33',
  'background_color' => 'accent',
]));
$header($s, 'Three layers', 'A possible architecture', 'Three layers. One public purpose.', 'Each technology has a different job. The opportunity is to connect them without confusing their responsibilities.');
foreach ([
  ['01', 'Foundation', 'Digital public infrastructure', 'Shared systems and APIs for identity, consent, payments, credentials, and service delivery.', 'primary'],
  ['02', 'Experience', 'Open source and Drupal', 'Structured, governed, multilingual information and interoperable citizen-facing experiences.', 'primary'],
  ['03', 'Interaction', 'Artificial intelligence', 'Intent-based discovery, grounded explanations, language and voice interfaces, and carefully authorized actions.', 'secondary'],
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
// 4. Citizen journey.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Citizen journey', $section([
  'columns' => '25-25-25-25',
  'background_color' => 'black',
  'dark_background' => TRUE,
]));
$g = $add('sdc.event_horizon.group', 'Group - Citizen journey header', $group('column', 'md'), $s, 'header_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Start with a person, not a platform', ['text' => 'Start with a person, not a platform', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - What should public access feel like?', $heading('What should public access feel like?', 2, TRUE), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Small business question', $text('<p><em>“I’ve started a small business. Which registrations, programmes, or benefits should I know about?”</em></p>', 'text-2xl', TRUE), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - A simple question', $text('<p>A simple question can involve several departments, eligibility rules, languages, and steps.</p>', 'normal', TRUE), $g, 'group_slot');
foreach ([
  ['01', 'Discover authoritative services', 'primary'],
  ['02', 'Explain eligibility and requirements', 'primary'],
  ['03', 'Help in a preferred language', 'primary'],
  ['04', 'Guide the next authorized action', 'secondary'],
] as [$marker, $title, $accent]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'marker' => $marker,
    'title' => $title,
    'accent' => $accent,
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 5. Questions worth asking.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Questions worth asking', $section([
  'columns' => '50-50',
]));
$header($s, 'Questions', 'On the table', 'Questions worth asking', 'Real questions about public value, architecture, access, and responsibility. Not a platform pitch.');
foreach ([
  'Where can open source contribute more to public digital systems?',
  'Where can Drupal add value without replacing the underlying DPI?',
  'How can people find services by intent, not department?',
  'How do we make multilingual AI explanations accurate and accessible?',
  'What consent, evidence, and safeguards should citizen-facing AI agents require?',
  'What could a useful, responsibly scoped pilot look like?',
] as $i => $question) {
  $add('drupal_india_connect.info-card', 'Info card - Question ' . ($i + 1), [
    'marker' => sprintf('%02d', $i + 1),
    'title' => $question,
    'accent' => 'primary',
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 6. Moderator and panelists.
// ---------------------------------------------------------------------------
// People Cards go in a grid, as on /ai-summit; add each panelist there as
// they confirm, and remove the dashed callout once the panel is complete.
$s = $add('sdc.event_horizon.section', 'Section - The panel', $section([
  'background_color' => 'accent',
  'section_footer' => TRUE,
]));
$header($s, 'The panel', 'People in the conversation', 'A cross-disciplinary panel', 'We are bringing together perspectives from public infrastructure, open source, AI, and governance. Panelist announcements will follow.');
$people = $add('drupal_india_connect.grid', 'Grid - Panel', ['columns' => 3, 'gap' => '24'], $s, 'main_slot');
$add('drupal_india_connect.people-card', 'People card - Piyuesh Kumar', [
  'name' => 'Piyuesh Kumar',
  'designation' => 'Moderator · QED42',
  'description' => $html('<p>Guiding a practical conversation across technology, public systems, and citizen experience.</p>'),
], $people, 'grid_slot');
$add('drupal_india_connect.callout', 'Callout - Panelists to be announced', [
  'icon' => 'users-three',
  'heading' => 'Panelists to be announced: four to five voices',
  'text' => 'We’re shaping a balanced discussion with practitioners from different disciplines.',
  'note' => 'Names and profiles will be added as participation is confirmed.',
  'style' => 'dashed',
], $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 7. Takeaway / CTA.
// ---------------------------------------------------------------------------
// The CTA's own background only fills the content column, so the colour sits
// on a full-width section instead, as on the AI Summit page.
$s = $add('sdc.event_horizon.section', 'Section - CTA Better public services', $section([
  'background_color' => 'secondary',
  'padding_block_start' => '0',
  'padding_block_end' => '0',
  'section_header' => FALSE,
]));
$cta = $add('sdc.event_horizon.cta', 'CTA - Better public services, built together', [
  'heading_text' => 'Better public services, built together.',
  'level' => 2,
  'text' => 'Leave with a clearer view of where open-source platforms, Drupal, and AI can support public infrastructure, and what it takes to keep the experience trustworthy, inclusive, and accountable.',
  'text_align' => 'center',
  'overlay_opacity' => '0%',
], $s, 'main_slot');
$add('sdc.event_horizon.button', 'Button - Explore the AI Summit', [
  'variant' => 'primary-inverted',
  'label' => 'Explore the AI Summit',
  'href' => ['uri' => 'internal:/ai-summit', 'options' => []],
  'size' => 'medium',
  'icon' => 'arrow-right',
  'mobile_width' => FALSE,
  'icon_first' => FALSE,
  'disabled' => FALSE,
], $cta, 'actions');

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
    'title' => 'Building Intelligent Public Infrastructure: Open Source, Drupal, and AI',
    'description' => 'An AI Summit panel discussion at DrupalAsia Connect India 2027, IIT Bombay, 28 January 2027: how open source, Drupal and AI can make public services easier to find, understand and use.',
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
$page->setRevisionLogMessage('Build AI Summit panel discussion page (issue #130).');
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
