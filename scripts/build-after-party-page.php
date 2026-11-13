<?php

/**
 * @file
 * Builds the After party (Drupal birthday celebrations) canvas_page at
 * /conference/after-party, unpublished. Same composition as the Day 2 panel
 * page (/conference/whats-next-for-drupal).
 *
 * Run: ddev drush php:script scripts/build-after-party-page.php -- apply
 *
 * Stops if a page already has the alias: rebuilding replaces every row and
 * would discard editor changes. Pass `rebuild` instead of `apply` to do that
 * deliberately (refused while the page has a Canvas autosave draft).
 *
 * Needs the cake, fork-knife and confetti icons (release 1.4.24). Deploys are
 * code-only: run this against each environment's database after that ships.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$mode = $extra[0] ?? '';
if (!in_array($mode, ['apply', 'rebuild'], TRUE)) {
  echo "Pass `apply` (or `rebuild`) to write: ddev drush php:script scripts/build-after-party-page.php -- apply\n";
  return;
}

$alias = '/conference/after-party';
$etm = \Drupal::entityTypeManager();
$component_storage = $etm->getStorage('component');
$uuid_gen = new UuidGen();
$rows = [];

// --- Tree helpers: each returns a node [component, label, inputs, slots]. ---
$n = fn(string $id, string $label, array $inputs, array $slots = []) => [$id, $label, $inputs, $slots];
$html = fn(string $h) => ['value' => $h, 'format' => 'canvas_html_block'];
$link = fn(string $uri) => ['uri' => $uri, 'options' => []];

$section = fn(string $label, array $props, array $slots) => $n('event_horizon.section', "Section - $label", $props + [
  'width' => '100%', 'columns' => '100', 'mobile_columns' => '1',
  'margin_block_start' => '0', 'margin_block_end' => '0', 'padding_block_start' => '64', 'padding_block_end' => '64',
  'section_header' => FALSE, 'section_footer' => FALSE, 'full_width' => TRUE, 'dark_background' => FALSE,
], $slots);
$group = fn(string $label, array $items, string $gap = 'sm', string $dir = 'column') => $n('event_horizon.group', "Group - $label", ['flex_direction' => $dir, 'flex_gap' => $gap, 'items_align' => 'start', 'flex_align' => 'start'], ['group_slot' => $items]);
$eyebrow = fn(string $t, string $tone = 'accent') => $n('drupal_india_connect.eyebrow', "Eyebrow - $t", ['text' => $t, 'tone' => $tone]);
$h = fn(string $t, int $level = 2, string $size = 'heading-responsive-3xl', string $color = 'default') => $n('event_horizon.heading', "Heading - $t", ['heading_text' => $t, 'level' => $level, 'text_size' => $size, 'text_color' => $color, 'align' => 'left']);
$p = fn(string $label, string $h, string $size = 'text-lg', string $color = 'default') => $n('event_horizon.text', "Text - $label", ['text' => $html($h), 'text_size' => $size, 'text_color' => $color]);
$btn = fn(string $variant, string $label, string $uri, ?string $icon = NULL) => $n('event_horizon.button', "Button - $label", ['variant' => $variant, 'label' => $label, 'href' => $link($uri), 'size' => 'medium', 'mobile_width' => FALSE, 'icon_first' => FALSE, 'disabled' => FALSE] + ($icon ? ['icon' => $icon] : []));
$grid = fn(string $label, array $props, array $items) => $n('drupal_india_connect.grid', "Grid - $label", $props + ['columns' => 1, 'gap' => '0'], ['grid_slot' => $items]);
$card = fn(string $label, array $props) => $n('drupal_india_connect.info-card', "Info card - $label", (isset($props['body']) ? ['body' => $html($props['body'])] + $props : $props) + ['accent' => 'primary']);
$fact = fn(string $title, string $text) => $n('drupal_india_connect.fact', "Fact - $title", ['title' => $title, 'text' => $text, 'accent' => 'primary', 'layout' => 'inline', 'tone' => 'on-dark']);

// --- Content. ---
$tree = [
  $section('Hero', ['background_color' => 'black', 'dark_background' => TRUE, 'columns' => '67-33', 'grid_align' => 'start'], [
    'main_slot' => [
      $group('Hero', [
        $eyebrow('Day 2 · After party', 'on-dark'),
        $h('After party', 1, 'heading-responsive-5xl', 'inverted'),
        $p('Hero subtitle', '<p>Drupal birthday celebrations</p>', 'text-2xl', 'inverted'),
        $p('Hero lede', '<p>Drupal turned 26 on 15 January. Two weeks later, we close Day 2 by celebrating it together, with the people who build it, use it and keep it going.</p>', 'text-lg', 'inverted'),
        $group('Hero buttons', [
          $btn('primary', 'Get tickets', 'internal:/#tickets', 'arrow-right'),
          $btn('secondary-inverted', 'See the full program', 'internal:/program'),
        ], 'sm', 'row'),
      ], 'lg'),
      $group('At a glance', [
        $eyebrow('At a glance', 'on-dark'),
        $grid('At a glance', ['variant' => 'panel'], [
          $fact('When', 'Friday, 29 January 2027'),
          $fact('Time', '18:30 – 21:00, right after the closing ceremony'),
          $fact('Where', 'Venue to be announced'),
          $fact('Who', 'Details to be announced'),
          $fact('Part of', 'DrupalAsia Connect India 2027 · Day 2, Discover'),
        ]),
      ]),
    ],
  ]),

  $section('Twenty-six years of Drupal', ['columns' => '50-50', 'grid_align' => 'center'], [
    'main_slot' => [
      $group('Why a birthday', [
        $eyebrow('Why a birthday'),
        $h('Twenty-six years of Drupal'),
        $p('Drupal 1.0', '<p>Drupal 1.0 was released on 15 January 2001. Drupal communities around the world mark the date each January, and this year our conference falls two weeks after it.</p>', 'normal'),
        $p('Not a talk', '<p>So the last thing on Day 2 is not a talk. It is an evening with the speakers, sponsors, volunteers and attendees you spent the day with, and a cake.</p>', 'normal'),
      ], 'md'),
      $card('Pull quote', ['style' => 'quote', 'pill' => 'The idea', 'title' => 'Twenty-six years of code, community and contribution. Come and celebrate it with us.', 'accent' => 'secondary']),
    ],
  ]),

  $section('One evening, no slides', ['background_color' => 'accent', 'section_header' => TRUE], [
    'header_slot' => [
      $group('What to expect', [
        $eyebrow('What to expect'),
        $h('One evening, no slides'),
        $p('What to expect lede', '<p>After a full day of sessions, this is the time to talk without a clock running.</p>'),
      ]),
    ],
    'main_slot' => [
      $grid('What to expect', ['variant' => 'joined-plus', 'columns' => 4], [
        $card('Celebrate', ['icon' => 'cake', 'pill' => 'Celebrate', 'title' => 'Cut the birthday cake', 'body' => '<p>We cut a cake for Drupal’s 26th, together.</p>']),
        $card('Connect', ['icon' => 'users-three', 'pill' => 'Connect', 'title' => 'Meet the people behind the talks', 'body' => '<p>Speakers, sponsors and volunteers, in one room.</p>']),
        $card('Unwind', ['icon' => 'fork-knife', 'pill' => 'Unwind', 'title' => 'Food and conversation', 'body' => '<p>Dinner is served. Menu to be announced.</p>', 'tbc' => TRUE]),
        $card('Remember', ['icon' => 'confetti', 'pill' => 'Remember', 'title' => 'Close the conference days', 'body' => '<p>Day 3 is for building. Tonight is for the community.</p>', 'accent' => 'secondary']),
      ]),
    ],
  ]),

  $section('From closing ceremony to cake', ['section_header' => TRUE], [
    'header_slot' => [
      $group('How the evening runs', [
        $eyebrow('How the evening runs'),
        $h('From closing ceremony to cake'),
      ]),
    ],
    'main_slot' => [
      $grid('How the evening runs', ['variant' => 'joined-arrow', 'columns' => 3], [
        $card('18:30 Doors open', ['marker' => '18:30', 'pill' => 'Arrive', 'title' => 'Doors open', 'body' => '<p>Straight from the closing ceremony.</p>', 'tbc' => TRUE]),
        $card('19:00 Birthday cake', ['marker' => '19:00', 'pill' => 'Celebrate', 'title' => 'Drupal’s birthday cake', 'body' => '<p>A few words, then the cake.</p>', 'tbc' => TRUE]),
        $card('19:15 Dinner', ['marker' => '19:15', 'pill' => 'Unwind', 'title' => 'Dinner and conversation', 'body' => '<p>Until 21:00.</p>', 'tbc' => TRUE, 'accent' => 'secondary']),
      ]),
    ],
  ]),

  $section('CTA Stay for the celebration.', ['background_color' => 'secondary', 'padding_block_start' => '0', 'padding_block_end' => '0'], [
    'main_slot' => [
      $n('event_horizon.cta', 'CTA - Stay for the celebration.', ['heading_text' => 'Stay for the celebration.', 'level' => 2, 'text' => 'The after party starts right after the closing ceremony on Day 2.', 'text_align' => 'center', 'text_color' => 'white', 'overlay_opacity' => '20%'], [
        'actions' => [
          $btn('primary-inverted', 'Get tickets', 'internal:/#tickets', 'arrow-right'),
          $btn('secondary-inverted', 'See the full program', 'internal:/program'),
        ],
      ]),
    ],
  ]),
];

// --- Flatten the tree into Canvas rows. ---
$emit = function (array $node, ?string $parent, ?string $slot) use (&$emit, &$rows, $component_storage, $uuid_gen): void {
  [$id, $label, $inputs, $slots] = $node;
  $entity = $component_storage->load("sdc.$id");
  if (!$entity || !$entity->status()) {
    throw new \RuntimeException("Component sdc.$id missing or disabled");
  }
  $uuid = $uuid_gen->generate();
  $rows[] = [
    'uuid' => $uuid,
    'parent_uuid' => $parent,
    'slot' => $parent ? $slot : NULL,
    'component_id' => "sdc.$id",
    'component_version' => $entity->getActiveVersion(),
    'inputs' => json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'label' => $label,
  ];
  foreach ($slots as $slot_name => $children) {
    foreach ($children as $child) {
      $emit($child, $uuid, $slot_name);
    }
  }
};
foreach ($tree as $node) {
  $emit($node, NULL, NULL);
}

// Icons the cards need must exist in the deployed theme.
$theme_path = \Drupal::service('extension.list.theme')->getPath('drupal_india_connect');
foreach (['cake', 'fork-knife', 'confetti', 'users-three'] as $icon) {
  if (!is_file(DRUPAL_ROOT . "/$theme_path/icons/phosphor/regular/$icon.svg")) {
    echo "Icon $icon.svg missing from the theme: deploy the release first. Nothing built.\n";
    return;
  }
}

// --- Save, with the CLAUDE.md guards. ---
$storage = $etm->getStorage('canvas_page');
$existing = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
$page = NULL;
if (preg_match('#^/page/(\d+)$#', $existing, $m)) {
  $page = $storage->load($m[1]);
  if ($mode !== 'rebuild') {
    echo "canvas_page {$page->id()} already has the alias $alias. Not touching it.\n";
    echo "Pass 'rebuild' to replace its components (discards any editor changes).\n";
    return;
  }
  if (\Drupal::keyValue('canvas.auto_save')->get("canvas_page:{$page->id()}:en")) {
    echo "canvas_page {$page->id()} has unpublished Canvas changes. Publish or discard them first.\n";
    return;
  }
}
if (!$page) {
  $page = $storage->create([
    'title' => 'After party (Drupal birthday celebrations)',
    'description' => 'The DrupalAsia Connect India 2027 after party, Friday 29 January 2027 from 18:30: celebrating Drupal’s 26th birthday after the Day 2 closing ceremony.',
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
$page->setRevisionLogMessage('Build the After party page.');
$page->save();

$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$before = array_column($rows, 'uuid');
$after = array_column($saved->get('components')->getValue(), 'uuid');
echo 'Saved canvas_page ', $saved->id(), " at $alias (", $saved->isPublished() ? 'published' : 'unpublished', '): ', count($after), ' rows (', count($before), " built).\n";
echo array_diff($before, $after) ? 'LOST ' . count(array_diff($before, $after)) . " rows\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
