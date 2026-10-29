<?php

/**
 * @file
 * Builds the Sponsors & Partners canvas_page.
 *
 * Run: ddev drush php:script scripts/build-sponsors-page.php
 *
 * Creates the page unpublished at /sponsors-and-partners; publishing it is
 * a separate, manual step. If a page already has the alias the script stops;
 * pass `rebuild` to replace its components (discards any editor changes):
 *   ddev drush php:script scripts/build-sponsors-page.php rebuild
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database after the release carrying the components it uses has shipped:
 * sponsor-logo and sponsor-group. Back up the database first.
 *
 * Sections: hero, then the in-kind sponsor and media partner. The closing
 * call to action (closing-cta) goes back in once there are more sponsors.
 * The sponsorship tiers (Diamond, then the 2027 brochure's packages in
 * docs/sponsors/) are left out until sponsors sign; editors add each one in
 * Canvas as a Sponsor group of Sponsor logo tiles above the in-kind band,
 * following design-system/ui_kits/event_site/sponsors-partners.html.
 *
 * The two real logos (DrupalEasy, The Drop Times) are imported from
 * design-system/assets/partners/ as media on first run.
 */

use Drupal\Component\Uuid\Php as UuidGen;
use Drupal\Core\File\FileExists;

$alias = '/sponsors-and-partners';
$become = 'internal:/become-a-sponsor';
$brochure = 'mailto:events@drupalasia.org?subject=Request%20for%20Sponsorship%20Brochure%20-%20DrupalAsia%20Connect%20India%202027';

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

/**
 * Media id for a logo, importing it from design-system/assets/partners/ the
 * first time. Matched by URI, since ids differ per environment.
 */
$logo = function (string $filename, string $alt) use ($etm): array {
  $uri = "public://sponsors/$filename";
  $files = $etm->getStorage('file')->loadByProperties(['uri' => $uri]);
  $file = $files ? reset($files) : NULL;
  if (!$file) {
    $source = DRUPAL_ROOT . "/../design-system/assets/partners/$filename";
    if (!is_readable($source)) {
      throw new \RuntimeException("Logo not found: $source");
    }
    $dir = 'public://sponsors';
    \Drupal::service('file_system')->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
    $file = \Drupal::service('file.repository')->writeData(file_get_contents($source), $uri, FileExists::Error);
    $file->setPermanent();
    $file->setOwnerId(1);
    $file->save();
  }
  $ids = $etm->getStorage('media')->getQuery()->accessCheck(FALSE)
    ->condition('bundle', 'image')
    ->condition('field_media_image.target_id', $file->id())
    ->range(0, 1)->execute();
  if ($ids) {
    return ['target_id' => (string) reset($ids)];
  }
  $media = $etm->getStorage('media')->create([
    'bundle' => 'image',
    'name' => $alt . ' logo',
    'uid' => 1,
    'status' => TRUE,
    'field_media_image' => ['target_id' => $file->id(), 'alt' => $alt],
  ]);
  $media->save();
  echo "Imported $filename as media {$media->id()}.\n";
  return ['target_id' => (string) $media->id()];
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
  'size' => 'large',
  'icon' => 'arrow-right',
  'mobile_width' => FALSE,
  'icon_first' => FALSE,
  'disabled' => FALSE,
];

// ---------------------------------------------------------------------------
// 1. Page head: title on the left, intro and actions on the right.
// ---------------------------------------------------------------------------
$hero = $add('drupal_india_connect.hero-split', 'Hero - Sponsors & partners', [
  'eyebrow' => 'Sponsors & partners',
  'title' => 'The organisations',
  'title_highlight' => 'behind the event',
]);
$aside = $add('sdc.event_horizon.group', 'Group - Hero intro', $group('column', 'md'), $hero, 'aside');
$add('sdc.event_horizon.text', 'Text - Hero lede', [
  'text' => $html('<p>DrupalAsia Connect India 2027 brings the Drupal community across Asia and beyond to IIT Bombay for three days: the AI Summit, a conference day of keynotes and workshops, and a day of contribution.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'default',
], $aside, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero body', [
  'text' => $html('<p>The event is run by volunteers. Sponsorship keeps tickets affordable and funds travel grants for community members across the region. Thank you to every organisation on this page.</p>'),
  'text_size' => 'normal',
  'text_color' => 'default',
], $aside, 'group_slot');
$actions = $add('sdc.event_horizon.group', 'Group - Hero buttons', $group('row', 'sm'), $aside, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Become a sponsor', $button('Become a sponsor', $become), $actions, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Request the brochure', $button('Request the brochure', $brochure, 'secondary-inverted'), $actions, 'group_slot');

// ---------------------------------------------------------------------------
// 2. In-kind sponsor and media partner, side by side. The sponsorship tiers
// go above this as sponsors sign; see the prototype for their layout.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - In-kind & media', $section());
$header($s, 'In-kind & media', 'In-kind & media', 'Support beyond sponsorship', 'Organisations backing the event with their services and their reach.');
$pair = $add('drupal_india_connect.grid', 'Grid - In-kind & media', ['columns' => 2, 'gap' => '24', 'tablet_columns' => '2'], $s, 'main_slot');
foreach ([
  ['In-kind sponsor', 'primary-50', 'in-kind', 'DrupalEasy', 'https://www.drupaleasy.com/', 'drupaleasy.png'],
  ['Media partner', 'primary-60', 'media', 'The Drop Times', 'https://www.thedroptimes.com/', 'the-drop-times.png'],
] as [$title, $accent, $anchor, $name, $url, $file]) {
  $g = $add('drupal_india_connect.sponsor-group', "Sponsor group - $title", [
    'title' => $title,
    'accent' => $accent,
    'anchor' => $anchor,
  ], $pair, 'grid_slot');
  $add('drupal_india_connect.sponsor-logo', "Sponsor logo - $name", [
    'name' => $name,
    'url' => $url,
    'logo' => $logo($file, $name),
    'featured' => FALSE,
  ], $g, 'content');
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
    'title' => 'Our Sponsors & Partners',
    'description' => 'The organisations supporting DrupalAsia Connect India 2027, 28–30 January 2027 at IIT Bombay, Mumbai.',
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
$page->setRevisionLogMessage('Build Sponsors & Partners page.');
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
