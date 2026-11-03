<?php

/**
 * @file
 * Adds the international delegate form section to /visa-information (#161).
 *
 * Run: ddev drush php:script scripts/add-visa-delegate-form.php
 *
 * The page was built by scripts/build-visa-page.php and may since have been
 * edited in Canvas, so this inserts one section into the existing component
 * tree instead of rebuilding it. The section goes straight after the hero
 * (or first, if editors renamed the hero), and the hero gets a button to the
 * form as its first button. Running it twice does nothing.
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database. Back up the database first.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/visa-information';
$form_url = 'https://forms.gle/X4i5xriADMTXVZZR8';
$section_label = 'Section - Visa assistance';

$etm = \Drupal::entityTypeManager();
$storage = $etm->getStorage('canvas_page');
$component_storage = $etm->getStorage('component');

$path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
if (!preg_match('#^/page/(\d+)$#', $path, $m) || !($page = $storage->load($m[1]))) {
  echo "No canvas_page at $alias. Run scripts/build-visa-page.php first.\n";
  return;
}
$existing = $page->get('components')->getValue();
if (in_array($section_label, array_column($existing, 'label'), TRUE)) {
  echo "canvas_page {$page->id()} already has '$section_label'. Nothing to do.\n";
  return;
}

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

$group = fn(string $direction, string $gap = 'md', array $extra = []) => $extra + [
  'flex_direction' => $direction,
  'flex_gap' => $gap,
  'items_align' => 'start',
  'flex_align' => 'start',
];

$button = fn(string $label, string $variant) => [
  'variant' => $variant,
  'label' => $label,
  'href' => ['uri' => $form_url, 'options' => []],
  'size' => 'medium',
  'icon' => 'arrow-right',
  'mobile_width' => FALSE,
  'icon_first' => FALSE,
  'disabled' => FALSE,
];

$text = fn(string $value) => [
  'text' => $html($value),
  'text_size' => 'text-lg',
  'text_color' => 'default',
];

// A muted band, so it stands apart from the hero and the plain band below.
// Full width with a 75-25 grid like the hero, not 'width' => '75%': on a
// full-width band that percentage is of the viewport, so the text would sit
// wider than the plain bands' text.
$s = $add('sdc.event_horizon.section', $section_label, [
  'width' => '100%',
  'columns' => '75-25',
  'mobile_columns' => '1',
  'margin_block_start' => '0',
  'margin_block_end' => '0',
  'padding_block_start' => '64',
  'padding_block_end' => '64',
  'section_header' => TRUE,
  'section_footer' => FALSE,
  'full_width' => TRUE,
  'dark_background' => FALSE,
  'background_color' => 'muted',
]);

$g = $add('sdc.event_horizon.group', 'Group - Visa assistance header', $group('column', 'sm'), $s, 'header_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - International delegates', ['text' => 'International delegates', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Need help with your visa?', [
  'heading_text' => 'Need help with your visa?',
  'level' => 2,
  'text_size' => 'heading-responsive-3xl',
  'text_color' => 'default',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Visa assistance intro', $text('<p>If you are an attendee, speaker or sponsor delegate travelling from outside India and need visa assistance, please fill in the international delegate form at the earliest.</p>'), $g, 'group_slot');

$g = $add('sdc.event_horizon.group', 'Group - Visa assistance', $group('column', 'md'), $s, 'main_slot');
$add('sdc.event_horizon.text', 'Text - Visa assistance: Why', $text('<p>We are collecting these details to prepare the international delegates\' list required for submission to the Ministry of External Affairs (MEA), as part of the conference clearance process. Fill in one response per delegate, with your details exactly as shown on your passport.</p>'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Visa assistance: Please note', $text('<p><strong>Please note:</strong> filling in this form is not a confirmation of participation, registration, invitation or visa approval. It is only for collecting the information needed for MEA documentation.</p>'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Visa assistance: Questions', $text('<p><strong>Questions?</strong> Ask in the #drupalcon_asia channel on <a href="https://www.drupal.org/slack">Drupal Slack</a>, or email <a href="mailto:events@drupalasia.org">events@drupalasia.org</a>.</p>'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Fill in the delegate form', $button('Fill in the delegate form', 'primary'), $g, 'group_slot');

// Siblings render in array order, so what matters is position among rows with
// the same parent and slot. The section goes right after the hero's
// top-level row, or first.
$find = function (string $label, bool $top_level) use ($existing): ?int {
  foreach ($existing as $i => $row) {
    if ($row['label'] === $label && ($row['parent_uuid'] === NULL) === $top_level) {
      return $i;
    }
  }
  return NULL;
};
$hero = $find('Section - Hero', TRUE);
$new = $existing;
array_splice($new, $hero === NULL ? 0 : $hero + 1, 0, $rows);
echo 'Inserting ', count($rows), ' rows ', $hero === NULL ? 'first' : 'after the hero', ".\n";

// Hero button: before the hero's existing buttons, so it is the first one.
$hero_buttons = $find('Group - Hero buttons', FALSE);
if ($hero_buttons === NULL) {
  echo "No 'Group - Hero buttons' row: skipping the hero button.\n";
}
else {
  $rows = [];
  $group_uuid = $existing[$hero_buttons]['uuid'];
  $add('sdc.event_horizon.button', 'Button - Visa assistance form', $button('Visa assistance form', 'primary-inverted'), $group_uuid, 'group_slot');
  foreach ($new as $i => $row) {
    if ($row['parent_uuid'] === $group_uuid) {
      array_splice($new, $i, 0, $rows);
      echo "Adding the hero button.\n";
      break;
    }
  }
}

// Save, with the guards from CLAUDE.md.
$page->set('components', $new);
$violations = $page->validate();
foreach ($violations as $v) {
  echo 'VIOLATION ', $v->getPropertyPath(), ': ', strip_tags((string) $v->getMessage()), "\n";
}
if (count($violations)) {
  echo "Not saved.\n";
  return;
}
$page->setNewRevision(TRUE);
$page->setRevisionLogMessage('Add international delegate form (MEA clearance) to the Visa information page (issue #161).');
$page->save();

$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$before = array_column($new, 'uuid');
$after = array_column($saved->get('components')->getValue(), 'uuid');
$lost = array_diff($before, $after);
echo 'Saved canvas_page ', $saved->id(), ' at ', $alias, ': ', count($after), ' rows (', count($existing), " before).\n";
echo $lost ? 'LOST ' . count($lost) . " rows\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
