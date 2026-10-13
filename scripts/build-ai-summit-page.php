<?php

/**
 * @file
 * Rebuilds the AI Summit canvas_page at /ai-summit (issue #105).
 *
 * Run: ddev drush php:script scripts/build-ai-summit-page.php rebuild
 *
 * This replaces every component row on the existing page, so it refuses to
 * run without `rebuild`, and refuses while a Canvas editor draft of the page
 * exists (an open editor tab would write its old tree back over the new one,
 * see CLAUDE.md). Pass `discard-draft` as well to delete that draft.
 *
 * Content that already lives on the page is carried over rather than
 * retyped: the hero photo, the seven domain illustrations and the speaker
 * People Cards (matched by heading / name, not by row position). If one of
 * them cannot be found the script stops instead of guessing.
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database by hand. Back up the database first (`ddev export-db`, or a
 * mysqldump on the server). Copy is taken from the approved prototype
 * (drupalasia-ai-summit-page.html, attached to #105).
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/ai-summit';
$args = $extra ?? [];

$etm = \Drupal::entityTypeManager();
$component_storage = $etm->getStorage('component');
$storage = $etm->getStorage('canvas_page');
$uuid_gen = new UuidGen();

$path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
if (!preg_match('#^/page/(\d+)$#', $path, $m) || !($page = $storage->load($m[1]))) {
  echo "No canvas_page at $alias. Nothing to rebuild.\n";
  return;
}
if (!in_array('rebuild', $args, TRUE)) {
  echo "This replaces every component on canvas_page {$page->id()} ($alias), discarding editor changes.\n";
  echo "Back up the database, then pass 'rebuild'.\n";
  return;
}
$draft_key = 'canvas_page:' . $page->id() . ':' . $page->language()->getId();
$drafts = \Drupal::keyValue('canvas.auto_save');
if ($drafts->has($draft_key)) {
  if (!in_array('discard-draft', $args, TRUE)) {
    echo "A Canvas editor draft of this page exists ($draft_key). Publishing it later would undo this rebuild.\n";
    echo "Ask whoever is editing it, then pass 'discard-draft' as well to delete it.\n";
    return;
  }
  $drafts->delete($draft_key);
  echo "Deleted Canvas draft $draft_key.\n";
}

// ---------------------------------------------------------------------------
// Carry over what is already on the page.
// ---------------------------------------------------------------------------
$old = $page->get('components')->getValue();
$key = fn(string $s) => preg_replace('/[^a-z0-9]+/', ' ', strtolower(html_entity_decode(strip_tags($s))));

$hero_media = NULL;
$domain_media = [];
$people = [];
foreach ($old as $row) {
  $in = json_decode($row['inputs'], TRUE) ?: [];
  switch ($row['component_id']) {
    case 'sdc.event_horizon.hero-side-by-side':
      $hero_media = $in['media'] ?? NULL;
      break;

    case 'sdc.event_horizon.card':
      if (isset($in['heading_text'], $in['media'])) {
        $domain_media[trim($key($in['heading_text']))] = $in['media'];
      }
      break;

    case 'sdc.drupal_india_connect.people-card':
      $people[trim($key($in['name'] ?? ''))] = $row;
      break;
  }
}

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

$heading = fn(string $text, int $level = 2, string $size = 'heading-responsive-3xl', string $color = 'default') => [
  'heading_text' => $text,
  'level' => $level,
  'text_size' => $size,
  'text_color' => $color,
  'align' => 'left',
];

$text = fn(string $value, string $size = 'normal', string $color = 'default') => [
  'text' => $html($value),
  'text_size' => $size,
  'text_color' => $color,
];

/**
 * Section header: eyebrow, h2 and up to two intro paragraphs.
 *
 * The h2 must be the band's first heading: js/section-anchors.js reads it to
 * give the band its anchor id.
 */
$header = function (string $section_uuid, string $name, string $eyebrow, string $title, array $intro = [], bool $dark = FALSE) use ($add, $group, $heading, $text): string {
  $g = $add('sdc.event_horizon.group', "Group - $name header", $group('column', 'sm'), $section_uuid, 'header_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - $eyebrow", ['text' => $eyebrow, 'tone' => $dark ? 'on-dark' : 'accent'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - $title", $heading($title, 2, 'heading-responsive-3xl', $dark ? 'inverted' : 'default'), $g, 'group_slot');
  foreach (array_values($intro) as $i => [$copy, $size]) {
    $add('sdc.event_horizon.text', "Text - $name intro " . ($i + 1), $text("<p>$copy</p>", $size, $dark ? 'inverted' : 'default'), $g, 'group_slot');
  }
  return $g;
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

// Tickets live in the homepage's "Choose Your Experience" band (#tickets),
// the same target as the main menu's Tickets link.
$tickets = 'internal:/#tickets';

// ---------------------------------------------------------------------------
// 1. Hero. Keeps the page's existing photo.
// ---------------------------------------------------------------------------
if (!$hero_media) {
  throw new \RuntimeException('Existing hero photo not found on the page.');
}
$hero = $add('sdc.event_horizon.hero-side-by-side', 'Hero - AI Summit', [
  'hero_flex_gap' => 'large',
  'justify_content' => 'center',
  'hero_flex_direction_mobile' => 'vertical',
  'padding_block_start' => '0',
  'padding_block_end' => '0',
  'image_size' => '4:3',
  'image_position' => 'right',
  'image_radius' => 'small',
  'media' => $hero_media,
]);
$s = $add('sdc.event_horizon.section', 'Section - Hero', $section([
  'margin_block_start' => '48',
  'margin_block_end' => '48',
  'padding_block_start' => '0',
  'padding_block_end' => '0',
  'full_width' => FALSE,
  'section_footer' => TRUE,
]), $hero, 'hero_slot');
$g = $add('sdc.event_horizon.group', 'Group - Hero title', $group('column', 'sm'), $s, 'header_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Day 1 · Imagine', ['text' => 'Day 1 · Imagine', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - AI Summit', $heading('AI Summit', 1, 'heading-responsive-5xl', 'primary'), $g, 'group_slot');
$g = $add('sdc.event_horizon.group', 'Group - Hero intro', $group('column', 'md'), $s, 'main_slot');
$add('sdc.event_horizon.text', 'Text - Hero lede', $text("<p>AI is changing how we build, work and solve problems. Let's bring the people working through those changes into one room.</p>", 'text-xl'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', $text('<p>A community-led day of real-world perspectives across technology, industry, public services and open source. Beyond any single model, tool or platform.</p>'), $g, 'group_slot');
$b = $add('sdc.event_horizon.group', 'Group - Hero buttons', $group('row', 'sm'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Explore AI Summit tickets', $button('Explore AI Summit tickets', $tickets), $b, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Explore the programme', $button('Explore the programme', "internal:$alias#programme", 'secondary'), $b, 'group_slot');
// A grid, not an equal-width row group: the row never wraps, which squeezes
// three facts into ~100px columns on a phone. The grid steps 1 → 2 → 3.
$f = $add('drupal_india_connect.grid', 'Grid - Hero facts', ['columns' => 3, 'gap' => '24'], $s, 'footer_slot');
foreach ([
  ['Date', '28 January 2027', 'primary'],
  ['Venue', 'IIT Bombay · Mumbai, India', 'primary'],
  ['Part of', 'DrupalAsia Connect India 2027', 'secondary'],
] as [$caption, $value, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $caption", [
    'title' => $caption,
    'text' => $value,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'plain',
  ], $f, 'grid_slot');
}

// ---------------------------------------------------------------------------
// 2. Why we're gathering.
// ---------------------------------------------------------------------------
// Heading on the left, the longer intro on the right (three paragraphs at
// full width run to ~150 characters a line). The section's own columns do
// the split, so it stacks on phones; the numbered points go in the footer.
$s = $add('sdc.event_horizon.section', "Section - Why we're gathering", $section([
  'columns' => '33-67',
  'section_header' => FALSE,
  'section_footer' => TRUE,
]));
$g = $add('sdc.event_horizon.group', "Group - Why we're gathering title", $group('column', 'sm'), $s, 'main_slot');
$add('drupal_india_connect.eyebrow', "Eyebrow - Why we're gathering", ['text' => "Why we're gathering", 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Beyond the tools. Into the real world.', $heading('Beyond the tools. Into the real world.'), $g, 'group_slot');
$g = $add('sdc.event_horizon.group', "Group - Why we're gathering intro", $group('column', 'md'), $s, 'main_slot');
$add('sdc.event_horizon.text', "Text - Why we're gathering intro 1", $text('<p>The last few years have moved AI from research conversations into everyday products, services and decisions. The interesting questions are no longer only about what a model can do.</p>', 'text-lg'), $g, 'group_slot');
$add('sdc.event_horizon.text', "Text - Why we're gathering intro 2", $text('<p>Where is AI already useful? How do teams make it dependable? What does responsible adoption look like in public services, education, enterprise and community-built technology?</p>'), $g, 'group_slot');
$add('sdc.event_horizon.text', "Text - Why we're gathering intro 3", $text("<p>We want to make room for practitioners and domain experts to share the work behind the outcomes: what worked, what didn't and what still needs solving.</p>"), $g, 'group_slot');
foreach ([
  ['01', 'Real-world experience', 'Talk about deployed systems, operational choices and practical constraints.', 'primary'],
  ['02', 'More than generative AI', 'Look across the broader landscape, from infrastructure and applied models to people and policy.', 'primary'],
  ['03', 'People from different domains', 'Connect builders, researchers, decision-makers and open-source communities.', 'secondary'],
] as [$num, $title, $body, $accent]) {
  $points ??= $add('drupal_india_connect.grid', "Grid - Why we're gathering points", ['columns' => 3, 'gap' => '24'], $s, 'footer_slot');
  $add('drupal_india_connect.info-card', "Info card - $num $title", [
    'marker' => $num,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => $accent,
  ], $points, 'grid_slot');
}

// ---------------------------------------------------------------------------
// 3. AI across domains. The seven existing cards, with their illustrations.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - AI across domains', $section([
  'columns' => '25-25-25-25',
  'background_color' => 'accent',
]));
$header($s, 'AI across domains', 'Seven areas of conversation', 'AI across domains', [
  ['A broad look at where AI meets the systems, services and communities we depend on.', 'text-lg'],
]);
foreach ([
  ['Government & public sector', 'Digital public infrastructure, citizen services, responsible AI and adoption at scale.'],
  ['Enterprise & transformation', 'AI strategy, agentic workflows, decision-making and organisational change.'],
  ['Education & social impact', 'Learning, inclusion, accessibility and practical applications for social outcomes.'],
  ['AI infrastructure & MLOps', 'Model deployment, observability, data pipelines, cost and performance.'],
  ['CMS & digital experience', 'Content workflows, personalisation, search, discovery and AI-powered experiences.'],
  ['Marketing & customer experience', 'Customer insights, campaign automation, personalisation and better-informed decisions.'],
  ['Open source AI', 'Open models, local AI, shared infrastructure and community-led ecosystems.'],
] as [$title, $body]) {
  $media = $domain_media[trim($key($title))] ?? NULL;
  if (!$media) {
    throw new \RuntimeException("Existing illustration for the '$title' domain card not found on the page.");
  }
  $add('sdc.event_horizon.card', "Card - $title", [
    'background' => 'default',
    'style' => 'framed',
    'orientation' => 'vertical',
    'heading_text' => $title,
    'level' => 3,
    'media' => $media,
    'text' => $html("<p>$body</p>"),
    'is_text_centered' => TRUE,
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 4. Voices of the frontier. The existing People Cards, carried over as-is
// apart from their "Speaking on" domain wording. Issue #93 replaces this grid
// with the Featured Speaker view.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Voices of the frontier', $section(['section_footer' => TRUE]));
$header($s, 'Voices of the frontier', 'The people behind the conversations', 'Voices of the frontier', [
  ['Meet some of the people bringing real-world experience, research and diverse perspectives to the summit.', 'text-lg'],
]);
$grid = $add('drupal_india_connect.grid', 'Grid - Speakers', ['columns' => 4, 'gap' => '24'], $s, 'main_slot');
foreach ([
  'Muktesh Kandpal' => 'Enterprise AI & digital transformation',
  'Vidhatanand V.' => 'Marketing & customer experience',
  'Lakshmi Narasimhan Parthasarathy' => 'AI infrastructure & MLOps',
  'Dr. Abhijat Vichare' => 'AI education & social impact',
] as $name => $domain) {
  $row = $people[trim($key($name))] ?? NULL;
  if (!$row) {
    throw new \RuntimeException("Existing People Card for '$name' not found on the page.");
  }
  $in = json_decode($row['inputs'], TRUE);
  $in['domain'] = $domain;
  $row['inputs'] = json_encode($in, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $row['parent_uuid'] = $grid;
  $row['slot'] = 'grid_slot';
  $row['label'] = "People card - $name";
  $rows[] = $row;
}
$add('sdc.event_horizon.text', 'Text - More speakers', $text('<p>More speakers and session details will be announced as the programme takes shape.</p>', 'text-sm'), $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 5. How the day takes shape (#programme). Formats only, no running order.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - How the day takes shape', $section([
  'columns' => '33-33-33',
  'background_color' => 'black',
  'dark_background' => TRUE,
]));
$header($s, 'How the day takes shape', 'One day · Many perspectives', 'How the day takes shape', [
  ['We are building a programme around experience, exchange and the questions worth asking together.', 'text-lg'],
  ['These are planned conversation formats, not a confirmed running order. Session titles and timings will be published once finalised.', 'text-sm'],
], TRUE);
foreach ([
  ['Perspective', 'Keynotes that widen the view', "Big-picture reflections that connect what's happening today with the work and decisions ahead.", 'primary'],
  ['Practice', 'Stories from real systems', 'Practical examples from the people designing, deploying and operating AI in different environments.', 'primary'],
  ['Conversation', 'Questions across boundaries', "Discussion across builders, institutions, domains and communities, including the trade-offs that don't fit into a demo.", 'secondary'],
] as [$pill, $title, $body, $accent]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'pill' => $pill,
    'title' => $title,
    'body' => $html("<p>$body</p><p><em>Format being developed.</em></p>"),
    'accent' => $accent,
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 6. Leave with a broader lens.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Leave with a broader lens', $section(['columns' => '33-33-33']));
$header($s, 'Leave with a broader lens', "What you'll take home", 'Leave with a broader lens', [
  ['Not a checklist of tools to try next week. A stronger way to understand, question and build with AI.', 'text-lg'],
]);
foreach ([
  ['01', 'Understand', 'See beyond the headlines', 'Understand where AI is creating real value across different kinds of organisations and problems.', 'primary'],
  ['02', 'Evaluate', 'Ask better questions', 'Recognise the operational, technical and human trade-offs behind AI adoption.', 'primary'],
  ['03', 'Act', 'Think in systems', 'Move from simply using AI tools towards designing responsible workflows, products and experiences around them.', 'secondary'],
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
// 7. Who should join us.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Different roles. Shared questions.', $section([
  'columns' => '25-25-25-25',
  'background_color' => 'accent',
]));
$header($s, 'Who should join us', 'Who should join us', 'Different roles. Shared questions.', [
  ["You don't need to arrive with the same background. That is the point.", 'text-lg'],
]);
foreach ([
  ['Builders & engineers', 'Developers, architects, platform teams and open-source contributors.', 'primary'],
  ['Product & design teams', 'People deciding how AI should fit into useful, trustworthy experiences.', 'primary'],
  ['Researchers & educators', 'Those developing ideas, advancing learning and studying their effects.', 'primary'],
  ['Leaders & domain experts', 'People responsible for applying technology in organisations and public life.', 'secondary'],
] as [$title, $body, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $title", [
    'title' => $title,
    'text' => $body,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'plain',
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 8. The story behind the summit.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - The story behind the summit', $section([
  'columns' => '50-50',
  'section_header' => FALSE,
]));
$g = $add('sdc.event_horizon.group', 'Group - Story quote', $group('column', 'md'), $s, 'main_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - The story behind the summit', ['text' => 'The story behind the summit', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - How an AI-focused day became a summit', $heading('How an AI-focused day became a summit'), $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Story question', $text('<p><strong>“What would make this day meaningful beyond another set of AI talks?”</strong></p>', 'text-xl'), $g, 'group_slot');
$g = $add('sdc.event_horizon.group', 'Group - Story teaser', $group('column', 'md'), $s, 'main_slot');
$add('sdc.event_horizon.text', 'Text - Story teaser', $text('<p>We started with an AI-focused Day 1. The conversations quickly moved beyond tools and models towards the people, places and systems where AI is being put to work.</p>', 'text-lg'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Read how the idea evolved', $button('Read how the idea evolved', 'internal:/how-an-ai-focused-day-became-a-summit', 'secondary'), $g, 'group_slot');

// ---------------------------------------------------------------------------
// 9. Closing CTA: the page's one orange band.
// ---------------------------------------------------------------------------
$cta = $add('sdc.event_horizon.cta', 'CTA - Join us at IIT Bombay', [
  'heading_text' => 'Join us at IIT Bombay.',
  'level' => 2,
  'text' => 'One AI-focused day within three days of DrupalAsia Connect India 2027. Explore ticket options and plan your visit.',
  'text_align' => 'center',
  'background_color' => 'secondary',
  'overlay_opacity' => '0%',
]);
$add('sdc.event_horizon.button', 'Button - Explore ticket options', $button('Explore ticket options', $tickets, 'primary-inverted'), $cta, 'actions');
$add('sdc.event_horizon.button', 'Button - Explore DrupalAsia Connect', $button('Explore DrupalAsia Connect', 'internal:/', 'secondary'), $cta, 'actions');

// ---------------------------------------------------------------------------
// Save, with the guards from CLAUDE.md.
// ---------------------------------------------------------------------------
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
$page->setRevisionLogMessage('Rebuild AI Summit page (issue #105).');
$page->save();

// Re-load and confirm nothing was dropped.
$storage->resetCache([$page->id()]);
$saved = $storage->load($page->id());
$before = array_column($rows, 'uuid');
$after = array_column($saved->get('components')->getValue(), 'uuid');
$lost = array_diff($before, $after);
echo 'Saved canvas_page ', $saved->id(), ' at ', $alias, ': ', count($after), ' rows (', count($before), ' built, ', count($old), " before).\n";
echo $lost ? 'LOST ' . count($lost) . " rows\n" : "No rows lost.\n";
echo 'Post-save violations: ', count($saved->validate()), "\n";
