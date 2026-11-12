<?php

/**
 * @file
 * Adds the DrupalEasy training discounts band to the Tickets page (issue #182).
 *
 * Run: ddev drush php:script scripts/update-ticket-rates-drupaleasy.php -- apply
 *
 * The band goes straight after the Tickets band: who is offering it (the
 * DrupalEasy logo already in the media library), the discount for each
 * qualifying ticket, the three courses, and how to claim it. Its heading is
 * matched in js/section-anchors.js, which gives it the #drupaleasy-discounts
 * anchor, so change both together. The Tickets band's "More value with
 * DrupalEasy" note is pointed at the band instead of promising coupon details
 * later.
 *
 * Only those rows change; every other row is kept. Rows are found by
 * component and label, not uuid, so this runs against any environment, and a
 * re-run replaces the band rather than adding a second one. On the server,
 * prefix drush with `php -d disable_functions=` and back up the database
 * first; the band only gets its anchor once the release carrying the
 * section-anchors.js change is deployed.
 *
 * Course dates and prices are as DrupalEasy provided them for the issue.
 */

use Drupal\Component\Uuid\Php as UuidGen;

if (($extra[0] ?? '') !== 'apply') {
  echo "Pass `apply` to write: ddev drush php:script scripts/update-ticket-rates-drupaleasy.php -- apply\n";
  return;
}

$band_label = 'Section - DrupalEasy discounts';
$heading = 'Save on DrupalEasy training';
$courses = [
  ['AI Inside Drupal Essentials', '11 January 2027', 'Normally $600, <strong>$540 for attendees</strong>.', 'https://www.drupaleasy.com/academy/aide/course-information'],
  ['Professional Module Development', '12 January 2027', '<strong>Save $340</strong> off the course tuition as an attendee.', 'https://www.drupaleasy.com/academy/pmd/course-information'],
  ['Professional Single Directory Components', '1 February 2027', 'Normally $600, <strong>$540 for attendees</strong>.', 'https://www.drupaleasy.com/academy/sdc/course-information'],
];

$etm = \Drupal::entityTypeManager();
$path = \Drupal::service('path_alias.manager')->getPathByAlias('/ticket-rates-and-details');
if (!preg_match('#^/page/(\d+)$#', $path, $m)) {
  throw new \RuntimeException('No canvas_page at /ticket-rates-and-details.');
}
$page = $etm->getStorage('canvas_page')->load($m[1]);
$rows = $page->get('components')->getValue();
$before = array_column($rows, 'uuid');

// The band from a previous run, with all its descendants.
$old = [];
foreach ($rows as $r) {
  if ($r['parent_uuid'] === NULL && $r['label'] === $band_label) {
    $old[] = $r['uuid'];
  }
}
do {
  $count = count($old);
  foreach ($rows as $r) {
    if ($r['parent_uuid'] !== NULL && in_array($r['parent_uuid'], $old, TRUE) && !in_array($r['uuid'], $old, TRUE)) {
      $old[] = $r['uuid'];
    }
  }
} while (count($old) > $count);

$tickets = NULL;
$note = NULL;
foreach ($rows as $r) {
  if ($r['parent_uuid'] === NULL && $r['component_id'] === 'sdc.event_horizon.section' && $r['label'] === 'Section - Tickets') {
    $tickets = $r['uuid'];
  }
  if ($r['component_id'] === 'sdc.event_horizon.text' && $r['label'] === 'Text - More value with DrupalEasy') {
    $note = $r['uuid'];
  }
}
if (!$tickets) {
  throw new \RuntimeException('No Tickets band on /ticket-rates-and-details.');
}

$component_storage = $etm->getStorage('component');
$uuid_gen = new UuidGen();
$band = [];

/**
 * Adds a row to the band and returns its uuid.
 */
$add = function (string $component, string $label, array $inputs, ?string $parent = NULL, ?string $slot = NULL) use (&$band, $component_storage, $uuid_gen): string {
  $id = str_starts_with($component, 'sdc.') ? $component : "sdc.$component";
  $entity = $component_storage->load($id);
  if (!$entity || !$entity->status()) {
    throw new \RuntimeException("Component $id missing or disabled");
  }
  $uuid = $uuid_gen->generate();
  $band[] = [
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
 * Media id for an image file, matched by URI: ids differ per environment.
 */
$media = function (string $uri) use ($etm): array {
  $files = $etm->getStorage('file')->loadByProperties(['uri' => $uri]);
  $fid = $files ? reset($files)->id() : NULL;
  $ids = $fid ? $etm->getStorage('media')->getQuery()->accessCheck(FALSE)->condition('field_media_image.target_id', $fid)->range(0, 1)->execute() : [];
  if (!$ids) {
    throw new \RuntimeException("No media entity for $uri");
  }
  return ['target_id' => (string) reset($ids)];
};

$html = fn(string $value) => ['value' => $value, 'format' => 'canvas_html_block'];
$group = fn(string $direction, string $gap = 'md') => [
  'flex_direction' => $direction,
  'flex_gap' => $gap,
  'items_align' => 'start',
  'flex_align' => 'start',
];

// Same settings as the page's other bands (scripts/build-ticket-rates-page.php).
$s = $add('sdc.event_horizon.section', $band_label, [
  'width' => '100%',
  'columns' => '100',
  'mobile_columns' => '1',
  'margin_block_start' => '0',
  'margin_block_end' => '0',
  'padding_block_start' => '64',
  'padding_block_end' => '64',
  'section_header' => TRUE,
  'section_footer' => TRUE,
  'full_width' => TRUE,
  'dark_background' => FALSE,
]);
$g = $add('sdc.event_horizon.group', 'Group - DrupalEasy discounts header', $group('column', 'sm'), $s, 'header_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Training partner offer', ['text' => 'Training partner offer', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', "Heading - $heading", [
  'heading_text' => $heading,
  'level' => 2,
  'text_size' => 'heading-responsive-3xl',
  'text_color' => 'default',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - DrupalEasy discounts intro', [
  'text' => $html('<p>DrupalEasy is supporting DrupalAsia Connect India 2027 and AI Summit attendees with a discount on its upcoming Drupal training programmes. Two ticket types qualify.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'default',
], $g, 'group_slot');

// Who is offering it beside what each ticket gets, stacked below 1000px.
$split = $add('drupal_india_connect.grid', 'Grid - DrupalEasy offer', ['columns' => 2, 'gap' => '24', 'tablet_columns' => '1', 'variant' => 'default'], $s, 'main_slot');
$add('drupal_india_connect.sponsor-logo', 'Sponsor logo - DrupalEasy', [
  'name' => 'DrupalEasy',
  'url' => 'https://www.drupaleasy.com/',
  'logo' => $media('public://sponsors/drupaleasy.png'),
  'description' => 'Drupal career training and professional development, and an in-kind sponsor of DrupalAsia Connect India 2027.',
  'featured' => TRUE,
], $split, 'grid_slot');
$tiers = $add('drupal_india_connect.grid', 'Grid - DrupalEasy discount by ticket', ['columns' => 2, 'gap' => '24', 'tablet_columns' => '2', 'variant' => 'default'], $split, 'grid_slot');
foreach ([
  ['15%', 'AI Summit + Conference', 'For holders of an AI Summit + Conference combo ticket.', 'secondary'],
  ['10%', 'Conference Only', 'For holders of a Conference Only ticket.', 'primary'],
] as [$marker, $ticket, $body, $accent]) {
  $add('drupal_india_connect.info-card', "Info card - DrupalEasy $marker off: $ticket", [
    'marker' => $marker,
    'marker_suffix' => 'off',
    'title' => $ticket,
    'body' => $html("<p>$body</p>"),
    'accent' => $accent,
    'link_label' => 'See this ticket',
    'link_url' => '#ticket-' . preg_replace('/[^a-z]+/', '-', strtolower($ticket)),
  ], $tiers, 'grid_slot');
}

$list = $add('sdc.event_horizon.group', 'Group - DrupalEasy courses', $group('column', 'md'), $s, 'main_slot');
$add('sdc.event_horizon.heading', 'Heading - Courses the discount applies to', [
  'heading_text' => 'Courses the discount applies to',
  'level' => 3,
  'text_size' => 'heading-responsive-xl',
  'text_color' => 'default',
  'align' => 'left',
], $list, 'group_slot');
$grid = $add('drupal_india_connect.grid', 'Grid - DrupalEasy courses', ['columns' => 3, 'gap' => '24', 'tablet_columns' => '1', 'variant' => 'default'], $list, 'group_slot');
foreach ($courses as [$title, $starts, $price, $url]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'icon' => 'calendar',
    'pill' => "Starts $starts",
    'title' => $title,
    'body' => $html("<p>$price</p>"),
    'accent' => 'primary',
    'link_label' => 'Course details',
    'link_url' => $url,
  ], $grid, 'grid_slot');
}

$add('drupal_india_connect.callout', 'Callout - How to claim the DrupalEasy discount', [
  'icon' => 'ticket',
  'heading' => 'How to claim',
  'text' => 'Show DrupalEasy your proof of purchase for an AI Summit + Conference or Conference Only ticket when you sign up for a course.',
  'note' => 'Other ticket types are not part of this offer.',
  'cta_label' => 'Get your ticket',
  'cta_url' => '#tickets',
  'style' => 'solid',
], $s, 'footer_slot');

// Rebuild the list: drop the old band, put the new one after Tickets, and
// point the Tickets band's DrupalEasy note at it.
$out = [];
foreach ($rows as $r) {
  if (in_array($r['uuid'], $old, TRUE)) {
    continue;
  }
  if ($r['uuid'] === $note) {
    $in = json_decode($r['inputs'], TRUE);
    $in['text'] = $html('<p>Your ticket can also save you money on Drupal training. <a href="#drupaleasy-discounts">See the DrupalEasy course discounts</a> for AI Summit + Conference (15% off) and Conference Only (10% off) ticket holders.</p>');
    $r['inputs'] = json_encode($in, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  $out[] = $r;
  if ($r['uuid'] === $tickets) {
    array_push($out, ...$band);
  }
}

$lost = array_diff($before, array_column($out, 'uuid'));
if (array_diff($lost, $old)) {
  throw new \RuntimeException('Unexpected row loss: ' . implode(', ', array_diff($lost, $old)));
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
$page->setRevisionLogMessage('Tickets: DrupalEasy training discounts band (issue #182).');
$page->save();

$etm->getStorage('canvas_page')->resetCache([$page->id()]);
$saved = $etm->getStorage('canvas_page')->load($page->id());
$after = array_column($saved->get('components')->getValue(), 'uuid');
echo "canvas_page {$saved->id()} (/ticket-rates-and-details): ", count($rows), ' rows -> ', count($after), ' (', count($old), ' old band rows replaced, ', count($band), " added).\n";
echo $note ? "  DrupalEasy note in the Tickets band updated.\n" : "  No DrupalEasy note in the Tickets band; left as is.\n";
echo array_diff(array_column($out, 'uuid'), $after) ? "  LOST rows on save\n" : "  No rows lost.\n";
echo '  Post-save violations: ', count($saved->validate()), "\n";
\Drupal::keyValue('canvas.auto_save')->delete("canvas_page:{$saved->id()}:en");
