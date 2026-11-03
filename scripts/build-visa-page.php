<?php

/**
 * @file
 * Builds the interim Visa information canvas_page (issue #161).
 *
 * Run: ddev drush php:script scripts/build-visa-page.php
 *
 * Creates the page unpublished at /visa-information; publish it by hand after
 * review. It says the full visa guidance is on its way and lists what attendees
 * can do now. Replace the "What we're preparing" section with the real
 * guidance once the visa details arrive.
 *
 * If a page already has that alias the script stops, because rebuilding
 * replaces every component row and would discard editor changes. Pass
 * `rebuild` to do that deliberately (local only):
 *   ddev drush php:script scripts/build-visa-page.php rebuild
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database. Back up the database first. Uses the same components and helpers
 * as scripts/build-travel-page.php.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/visa-information';

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
$link = fn(string $uri) => ['uri' => $uri, 'options' => []];

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

/**
 * Section header: eyebrow, h2 and an intro line, in the header slot.
 */
$header = function (string $section_uuid, string $name, string $eyebrow, string $heading, string $intro) use ($add, $group, $html): void {
  $g = $add('sdc.event_horizon.group', "Group - $name header", $group('column', 'sm'), $section_uuid, 'header_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - $eyebrow", ['text' => $eyebrow, 'tone' => 'accent'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - $heading", [
    'heading_text' => $heading,
    'level' => 2,
    'text_size' => 'heading-responsive-3xl',
    'text_color' => 'default',
    'align' => 'left',
  ], $g, 'group_slot');
  $add('sdc.event_horizon.text', "Text - $name intro", [
    'text' => $html("<p>$intro</p>"),
    'text_size' => 'text-lg',
    'text_color' => 'default',
  ], $g, 'group_slot');
};

$button = fn(string $label, string $uri, string $variant = 'primary') => [
  'variant' => $variant,
  'label' => $label,
  'href' => ['uri' => $uri, 'options' => []],
  'size' => 'medium',
  'icon' => 'arrow-right',
  'mobile_width' => FALSE,
  'icon_first' => FALSE,
  'disabled' => FALSE,
];

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
$add('drupal_india_connect.eyebrow', 'Eyebrow - Visa information', ['text' => 'Visa information', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Visa details are on their way', [
  'heading_text' => 'Visa details are on their way',
  'level' => 1,
  'text_size' => 'heading-responsive-5xl',
  'text_color' => 'inverted',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', [
  'text' => $html('<p>We are preparing everything international attendees need to apply for an Indian visa for DrupalAsia Connect India 2027. The full details will be published on this page by the second week of November 2026.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'inverted',
], $g, 'group_slot');
$facts = $add('sdc.event_horizon.group', 'Group - Hero facts', $group('row', 'lg', ['equal_children' => TRUE]), $g, 'group_slot');
foreach ([
  ['28–30 January 2027', 'Event dates', 'primary'],
  ['IIT Bombay, Mumbai', 'Venue', 'primary'],
  ['Mid-November 2026', 'Full visa details', 'secondary'],
] as [$text, $title, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $title", [
    'title' => $title,
    'text' => $text,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'on-dark',
  ], $facts, 'group_slot');
}
$buttons = $add('sdc.event_horizon.group', 'Group - Hero buttons', $group('row', 'sm'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Get event updates', $button('Get event updates', 'internal:/announcements', 'primary-inverted'), $buttons, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Travel & stay', $button('Travel & stay', 'internal:/travel-and-stay', 'primary'), $buttons, 'group_slot');

// ---------------------------------------------------------------------------
// 2. Reassurance.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - We know visas take time', $section(['width' => '75%']));
$header($s, 'We know visas take time', 'An update from the team', 'We know visas take time', 'Applying for a visa takes time, and so does the work behind it.');
$g = $add('sdc.event_horizon.group', 'Group - Reassurance', $group('column', 'md'), $s, 'main_slot');
foreach ([
  'Our side' => 'On our side, that means confirming the right visa route for conference attendees, setting up how we handle requests from attendees, and writing clear, step-by-step guidance you can rely on.',
  'Timeline' => 'Please be assured that our team is actively working on this, and we are on track to share the complete visa information by the second week of November. As soon as it is ready, it will be on this page and in our announcements.',
] as $name => $text) {
  $add('sdc.event_horizon.text', "Text - Reassurance: $name", [
    'text' => $html("<p>$text</p>"),
    'text_size' => 'text-lg',
    'text_color' => 'default',
  ], $g, 'group_slot');
}

// ---------------------------------------------------------------------------
// 3. What we're preparing.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', "Section - What we're preparing", $section([
  'columns' => '33-33-33',
  'background_color' => 'accent',
]));
$header($s, "What we're preparing", 'Coming soon', "What we're preparing", 'This page will cover:');
foreach ([
  ['01', 'Which visa to apply for', '<p>Guidance on the right visa category for attending the conference, with links to the official Government of India application portal.</p>'],
  ['02', 'Invitation letters', '<p>Whether you can request an invitation letter from the organisers, what it includes and how long it takes to receive.</p>'],
  ['03', 'When to apply', '<p>Recommended dates to apply by, so you have your visa well before the event starts on 28 January 2027.</p>'],
] as [$num, $title, $body]) {
  $add('drupal_india_connect.info-card', "Info card - $num $title", [
    'marker' => $num,
    'title' => $title,
    'body' => $html($body),
    'tbc' => TRUE,
    'accent' => 'primary',
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 4. What you can do now.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - What you can do now', $section(['columns' => '33-33-33']));
$header($s, 'What you can do now', 'In the meantime', 'What you can do now', 'A few steps you can take today, so you are ready when the full details are out.');
$add('drupal_india_connect.info-card', 'Info card - Check your passport', [
  'icon' => 'check',
  'title' => 'Check your passport',
  'body' => $html('<p>Make sure your passport is valid for at least six months from the date you arrive in India and has at least two blank pages.</p>'),
  'accent' => 'primary',
], $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Check e-Visa eligibility', [
  'icon' => 'airplane-tilt',
  'title' => 'Check e-Visa eligibility',
  'body' => $html('<p>The official Government of India portal lists which nationalities can apply online. Only use the official site.</p>'),
  'accent' => 'primary',
  'link_label' => 'indianvisaonline.gov.in',
  'link_url' => $link('https://indianvisaonline.gov.in/evisa/'),
], $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Plan your trip', [
  'icon' => 'calendar',
  'title' => 'Plan your trip',
  'body' => $html('<p>See how to reach IIT Bombay and where to stay nearby.</p>'),
  'accent' => 'primary',
  'link_label' => 'Travel & stay',
  'link_url' => $link('internal:/travel-and-stay'),
], $s, 'main_slot');

// ---------------------------------------------------------------------------
// 5. CTA.
// ---------------------------------------------------------------------------
$cta = $add('sdc.event_horizon.cta', 'CTA - Stay updated', [
  'heading_text' => 'Stay updated',
  'level' => 2,
  'text' => 'The full visa details will be announced by the second week of November 2026.',
  'text_align' => 'center',
  'background_color' => 'secondary',
  'overlay_opacity' => '0%',
]);
$add('sdc.event_horizon.button', 'Button - See announcements', $button('See announcements', 'internal:/announcements', 'primary-inverted'), $cta, 'actions');

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
    'title' => 'Visa information',
    'description' => 'Visa information for international attendees of DrupalAsia Connect India 2027 at IIT Bombay, Mumbai. Full details by the second week of November 2026.',
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
$page->setRevisionLogMessage('Build interim Visa information page (issue #161).');
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
