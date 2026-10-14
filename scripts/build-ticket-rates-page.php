<?php

/**
 * @file
 * Builds the redesigned Tickets & Registration canvas_page (issue #111).
 *
 * Run: ddev drush php:script scripts/build-ticket-rates-page.php
 *
 * Creates the page unpublished at /ticket-rates-details-preview, so the live
 * /ticket-rates-details page stays as it is until the content and marketing
 * teams sign off. Swapping the alias over is a separate, manual step. If a
 * page already has the preview alias the script stops; pass `rebuild` to
 * replace its components (discards any editor changes):
 *   ddev drush php:script scripts/build-ticket-rates-page.php rebuild
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database after the release carrying the components it uses has shipped:
 * hero-split (with its `image` option), and the ticket (`excludes`,
 * `benefit`, `featured`, `badge`, `anchor`), grid (`tablet_columns`),
 * countdown (`strip`), venue-location (`panel`) and info-card (`plain`)
 * options. Back up the database first.
 *
 * Sections: hero, tickets, travel, FAQ. The ticket cards themselves are
 * deliberately unchanged from the first build.
 *
 * Layout and copy follow design-system/ui_kits/event_site/ticket-rates.html,
 * itself built from the issue's prototype (drupalasia_ticket_rates_redesign
 * .html). Ticket prices, the Early Bird deadline and KonfHub checkout links
 * are the ones published on /ticket-rates-details at the time of writing;
 * the registration team still has to confirm them against KonfHub. The
 * Registration Team section deliberately has no people cards until its
 * members are confirmed.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/ticket-rates-details-preview';
$konfhub = 'https://konfhub.com/drupalasia-connect-india-2027';
$checkout = fn(string $ticket_id) => "https://konfhub.com/checkout/drupalasia-connect-india-2027?ticketId={$ticket_id}%7C1%3B";
$early_bird = 'Early Bird · until 10 December 2026, 11:59 PM IST or until sold out';

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
// 1. Hero: copy and buttons beside a community photo, then the Early Bird
// strip.
// ---------------------------------------------------------------------------
$add('drupal_india_connect.hero-split', 'Hero - Tickets & registration', [
  'eyebrow' => 'Tickets & registration',
  'title' => 'Choose your experience.',
  'title_highlight' => "Be part of what's next.",
  'lede' => "Three days to imagine new possibilities, discover what's being built and build together with the Drupal and open-source community.",
  'primary_label' => 'Buy tickets',
  'primary_url' => $link($konfhub),
  'secondary_label' => 'Compare tickets',
  'secondary_url' => $link('#tickets'),
  'image' => $media('public://2026-08/54945363309_e8224e51e0_k.jpg'),
  'image_tag_title' => '28–30 January 2027',
  'image_tag_text' => 'IIT Bombay, Powai, Mumbai',
]);
// Counts down to the Early Bird cutoff and swaps to the finished text after it.
$add('drupal_india_connect.countdown', 'Countdown - Early Bird ends', [
  'target' => '2026-12-10T23:59:00+05:30',
  'eyebrow' => 'Early Bird ends',
  'label' => '10 December 2026, 11:59 PM IST',
  'finished_text' => 'Early Bird has ended. Check KonfHub for current prices.',
  'variant' => 'strip',
  'link_label' => 'See Early Bird tickets',
  'link_url' => $link('#tickets'),
]);

// ---------------------------------------------------------------------------
// 2. Compare tickets: Full Combo featured on its own, the other four across,
// and the ticket guidance in the footer.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Tickets', $section([
  'background_color' => 'accent',
  'section_footer' => TRUE,
]));
$g = $add('sdc.event_horizon.group', 'Group - Tickets title', $group('column', 'sm'), $s, 'header_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Published Early Bird rates', ['text' => 'Published Early Bird rates', 'tone' => 'accent'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - Choose your ticket', [
  'heading_text' => 'Choose your ticket',
  'level' => 2,
  'text_size' => 'heading-responsive-3xl',
  'text_color' => 'default',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Tickets intro', [
  'text' => $html('<p>Simple choices, transparent inclusions, and a direct path to secure checkout on KonfHub.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'default',
], $g, 'group_slot');
$tickets = [
  [
    'eyebrow' => 'Complete experience',
    'title' => 'Full Combo',
    'text' => 'One pass for the AI Summit, the conference and the networking experience.',
    'includes' => 'AI Summit (28 Jan) · Conference (29–30 Jan) · Networking',
    'price' => '₹7,000',
    'availability' => $early_bird,
    'cta_label' => 'Get Full Combo',
    'cta_url' => $link($checkout('118788')),
    'accent' => 'secondary',
    'featured' => TRUE,
    'badge' => 'Broadest access',
    'anchor' => 'ticket-full-combo',
  ],
  [
    'eyebrow' => 'Early Bird',
    'title' => 'AI Summit + Conference',
    'text' => 'Take part in the summit and continue into the Drupal conference programme.',
    'includes' => 'AI Summit (28 Jan) · Conference (29–30 Jan)',
    'excludes' => 'Networking not included',
    'benefit' => 'DrupalEasy subscribers: 15% discount',
    'price' => '₹5,500',
    'availability' => $early_bird,
    // Four across, the full name wraps; the ticket adds it for screen readers.
    'cta_label' => 'Get this ticket',
    'anchor' => 'ticket-ai-summit-conference',
    'cta_url' => $link($checkout('118786')),
    'accent' => 'primary-50',
  ],
  [
    'eyebrow' => 'Early Bird',
    'title' => 'Conference + Networking',
    'text' => 'Two conference days and an extra opportunity to connect with the community.',
    'includes' => 'Conference (29–30 Jan) · Networking',
    'excludes' => 'AI Summit not included',
    'price' => '₹5,000',
    'availability' => $early_bird,
    // Four across, the full name wraps; the ticket adds it for screen readers.
    'cta_label' => 'Get this ticket',
    'anchor' => 'ticket-conference-networking',
    'cta_url' => $link($checkout('118787')),
    'accent' => 'primary-50',
  ],
  [
    'eyebrow' => 'Early Bird',
    'title' => 'Conference Only',
    'text' => 'For those coming for Drupal, digital experiences, contribution and community.',
    'includes' => 'Conference (29–30 Jan)',
    'excludes' => 'AI Summit not included · Networking not included',
    'benefit' => 'DrupalEasy subscribers: 10% discount',
    'price' => '₹2,500',
    'availability' => $early_bird,
    // Four across, the full name wraps; the ticket adds it for screen readers.
    'cta_label' => 'Get this ticket',
    'anchor' => 'ticket-conference-only',
    'cta_url' => $link($checkout('118735')),
    'accent' => 'primary-50',
  ],
  [
    'eyebrow' => 'Single-day experience',
    'title' => 'AI Summit Only',
    'text' => 'A focused day on where AI is creating meaningful change, beyond the hype.',
    'includes' => 'AI Summit (28 Jan)',
    'excludes' => 'Conference not included · Networking not included',
    'price' => '₹5,000',
    'availability' => 'Check current availability on KonfHub',
    // Four across, the full name wraps; the ticket adds it for screen readers.
    'cta_label' => 'Get this ticket',
    'anchor' => 'ticket-ai-summit-only',
    'cta_url' => $link($checkout('118733')),
    'accent' => 'primary-50',
  ],
];
$ticket_defaults = ['price_note' => 'per attendee', 'includes_list' => TRUE];
$featured = array_shift($tickets);
$add('drupal_india_connect.ticket', "Ticket - {$featured['title']}", $featured + $ticket_defaults, $s, 'main_slot');
// Two by two on tablets: three across would leave one ticket alone.
$grid = $add('drupal_india_connect.grid', 'Grid - Ticket cards', ['columns' => 4, 'gap' => '24', 'tablet_columns' => '2'], $s, 'main_slot');
foreach ($tickets as $ticket) {
  $add('drupal_india_connect.ticket', "Ticket - {$ticket['title']}", $ticket + $ticket_defaults, $grid, 'grid_slot');
}
// Guidance and the DrupalEasy offer as plain text, side by side from 1000px.
$notes = $add('drupal_india_connect.grid', 'Grid - Ticket notes', ['columns' => 2, 'gap' => '48', 'tablet_columns' => '1'], $s, 'footer_slot');
foreach ([
  ['Not sure which ticket to choose?', '<p>The Full Combo offers the broadest access. If you are coming mainly for the Drupal conference, Conference Only is the simplest choice. Workshops are booked separately and are not included in any ticket.</p>', NULL],
  ['More value with DrupalEasy', '<p>DrupalEasy subscribers can save on two DrupalAsia Connect India 2027 ticket categories. The benefit applies to AI Summit + Conference (15% off) and Conference Only (10% off).</p>', '<p>Coupon details will be shared before or during the event. The ticket prices shown do not include these discounts.</p>'],
] as [$heading, $body, $small]) {
  $note = $add('sdc.event_horizon.group', "Group - $heading", $group('column', 'sm'), $notes, 'grid_slot');
  $add('sdc.event_horizon.heading', "Heading - $heading", [
    'heading_text' => $heading,
    'level' => 3,
    'text_size' => 'heading-responsive-xl',
    'text_color' => 'default',
    'align' => 'left',
  ], $note, 'group_slot');
  $add('sdc.event_horizon.text', "Text - $heading", [
    'text' => $html($body),
    'text_size' => 'normal',
    'text_color' => 'default',
  ], $note, 'group_slot');
  if ($small) {
    $add('sdc.event_horizon.text', "Text - $heading terms", [
      'text' => $html($small),
      'text_size' => 'text-sm',
      'text_color' => 'default',
    ], $note, 'group_slot');
  }
}
$add('sdc.event_horizon.text', 'Text - Ticket note', [
  'text' => $html('<p>These are the rates currently published for DrupalAsia Connect India 2027. Ticket eligibility, availability, the final total, processing fees and any applicable taxes are confirmed at checkout on <a href="' . $konfhub . '">KonfHub</a>.</p>'),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 3. Travel & Stay.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Plan your trip', $section());
$header($s, 'Plan your trip', 'Your visit, made easier', 'Plan your trip to Mumbai', 'IIT Bombay is the gathering place. Get practical help with travel, arrival, accommodation and campus access before you book.');
// Venue panel beside the tips from 1000px, stacked above them below that.
$split = $add('drupal_india_connect.grid', 'Grid - Travel layout', ['columns' => 2, 'gap' => '24', 'tablet_columns' => '1'], $s, 'main_slot');
$add('drupal_india_connect.venue-location', 'Venue location - IIT Bombay', [
  'variant' => 'panel',
  'label' => 'Venue · Powai, Mumbai',
  'venue_name' => 'Meet us at IIT Bombay',
  'address' => 'Victor Menezes Convention Centre, Indian Institute of Technology Bombay, IIT Area, Powai, Mumbai, Maharashtra 400076',
  'description' => 'Room details will be confirmed closer to the event.',
  'tbc' => TRUE,
  'cta_label' => 'Plan travel & accommodation',
  'cta_url' => $link('internal:/travel-and-stay'),
  'secondary_cta_label' => 'Open in Google Maps',
  'secondary_cta_url' => $link('https://www.google.com/maps/search/?api=1&query=Victor+Menezes+Convention+Centre%2C+IIT+Bombay%2C+Powai%2C+Mumbai'),
], $split, 'grid_slot');
$tips = $add('drupal_india_connect.grid', 'Grid - Travel tips', ['columns' => 2, 'gap' => '24'], $split, 'grid_slot');
foreach ([
  ['airplane-tilt', 'Arriving by air', 'Fly into Mumbai (BOM), then continue to the campus by cab or public transport.', FALSE],
  ['map-pin', 'Stay near campus', 'Browse accommodation in Powai, Vikhroli and the surrounding neighbourhoods.', FALSE],
  ['check', 'Campus entry', 'Carry your registration confirmation and a government-issued photo ID. Entry details will be shared before the event.', TRUE],
  ['calendar', 'January in Mumbai', 'Expect typically pleasant, dry weather, and allow extra travel time for traffic.', FALSE],
] as [$icon, $title, $body, $tbc]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'icon' => $icon,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'tbc' => $tbc,
    'accent' => 'primary',
    'plain' => TRUE,
  ], $tips, 'grid_slot');
}

// ---------------------------------------------------------------------------
// 4. Before you register (FAQ): centred at 75% width, as on Travel & Stay.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Before you register', $section([
  'width' => '75%',
  'background_color' => 'accent',
]));
$header($s, 'Before you register', 'Before you register', 'Good to know', 'A few details worth checking before you complete your registration.');
$acc = $add('sdc.event_horizon.accordion-container', 'Accordion container - FAQ', [], $s, 'main_slot');
foreach ([
  ['Does my conference pass also include the AI Summit?', '<p>No. Choose AI Summit + Conference or Full Combo for both experiences. Conference Only and Conference + Networking do not include the AI Summit.</p>', FALSE],
  ['Are workshops included in the ticket price?', '<p>No. Workshops are booked separately and are not included in any ticket. Workshop booking details will be announced.</p>', TRUE],
  ['Is networking included with every ticket?', '<p>No. Networking is included with the Full Combo and Conference + Networking tickets. Check the ticket inclusions at checkout.</p>', FALSE],
  ['When do Early Bird prices end?', '<p>Early Bird prices run until 10 December 2026, 11:59 PM IST, or until sold out. Check KonfHub for the current status.</p>', FALSE],
  ['Where do I receive my ticket?', '<p>Registration and checkout are handled on <a href="' . $konfhub . '">KonfHub</a>. Use your correct attendee details and keep your registration confirmation for event check-in.</p>', FALSE],
  ['Where can I find travel, accommodation and venue information?', '<p>The <a href="/travel-and-stay">Travel &amp; Stay page</a> covers transport options, accommodation suggestions and visitor guidance. Room details and campus entry instructions will be published closer to the event.</p>', FALSE],
  ['Which event policies apply?', '<p>Every attendee agrees to the <a href="/code-of-conduct">Code of Conduct</a>. Refund and transfer terms will be published here once confirmed.</p>', TRUE],
] as [$q, $a, $tbc]) {
  $item = $add('sdc.event_horizon.accordion', "Accordion - $q", [
    'title' => $q,
    'heading_level' => 3,
    'open_by_default' => FALSE,
  ], $acc, 'accordion_content');
  $add('sdc.event_horizon.text', "Text - Answer: $q", [
    'text' => $html($a),
    'text_size' => 'normal',
    'text_color' => 'default',
  ], $item, 'accordion_content');
  if ($tbc) {
    $add('drupal_india_connect.tbc-tag', "TBC - $q", ['label' => 'TBC'], $item, 'accordion_content');
  }
}

// Phones only: keeps a way to buy within reach on a long page.
$add('drupal_india_connect.sticky-cta', 'Sticky CTA - Get tickets', [
  'label' => 'Get tickets',
  'url' => $link($konfhub),
  'note' => 'Early Bird from ₹2,500',
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
    'title' => 'Tickets & Registration',
    'description' => 'Compare tickets for DrupalAsia Connect India 2027 (28–30 January 2027, IIT Bombay, Mumbai) and register on KonfHub.',
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
$page->setRevisionLogMessage('Build Tickets & Registration page (issue #111).');
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
