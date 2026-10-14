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
 * database after the release carrying the ticket component's `excludes` and
 * `benefit` props has shipped. Back up the database first.
 *
 * Copy is taken from the issue's prototype (drupalasia_ticket_rates_redesign
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
$early_bird = 'Early Bird · until 23 October 2026, 11:59 PM IST or until sold out';

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
$add('drupal_india_connect.eyebrow', 'Eyebrow - Tickets & registration', ['text' => 'Tickets & registration', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', "Heading - Choose your experience. Be part of what's next.", [
  'heading_text' => "Choose your experience. Be part of what's next.",
  'level' => 1,
  'text_size' => 'heading-responsive-5xl',
  'text_color' => 'inverted',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', [
  'text' => $html("<p>Three days to imagine new possibilities, discover what's being built and build together with the Drupal and open-source community.</p>"),
  'text_size' => 'text-lg',
  'text_color' => 'inverted',
], $g, 'group_slot');
$facts = $add('sdc.event_horizon.group', 'Group - Hero facts', $group('row', 'lg', ['equal_children' => TRUE]), $g, 'group_slot');
foreach ([
  ['Dates', '28–30 January 2027', 'primary'],
  ['Venue', 'IIT Bombay, Powai, Mumbai', 'primary'],
  ['Early Bird', 'Until 23 October 2026, or until sold out', 'secondary'],
] as [$caption, $text, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $caption", [
    'title' => $caption,
    'text' => $text,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'on-dark',
  ], $facts, 'group_slot');
}
$buttons = $add('sdc.event_horizon.group', 'Group - Hero buttons', $group('row', 'sm'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Register on KonfHub', $button('Register on KonfHub', $konfhub, 'primary-inverted'), $buttons, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Compare tickets', $button('Compare tickets', '#tickets'), $buttons, 'group_slot');

// ---------------------------------------------------------------------------
// 2. Choose your experience.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Find your fit', $section(['columns' => '33-33-33']));
$header($s, 'Find your fit', 'Find your fit', 'One event. Different ways to join.', 'Whether AI is your focus, Drupal is your everyday work, or you want the entire experience, choose the pass that matches your plans.');
foreach ([
  ['01', 'Imagine', 'Explore AI', 'A dedicated summit connecting practical AI applications, decision-makers and the open ecosystem.', 'See AI Summit options'],
  ['02', 'Discover', 'Go deeper with Drupal', 'Conference sessions, ideas and real-world lessons across Drupal, digital experiences and the open web.', 'See conference passes'],
  ['03', 'Connect', 'Make more of the trip', 'Add networking to spend more time with the practitioners and people shaping the ecosystem.', 'Explore combination passes'],
] as [$num, $pill, $title, $body, $cta]) {
  $add('drupal_india_connect.info-card', "Info card - $num $title", [
    'marker' => $num,
    'pill' => $pill,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => $num === '03' ? 'secondary' : 'primary',
    'link_label' => $cta,
    'link_url' => $link('#tickets'),
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 3. Compare tickets, with the ticket guidance in the footer.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Tickets', $section([
  'background_color' => 'accent',
  'section_footer' => TRUE,
]));
$header($s, 'Tickets', 'Published Early Bird rates', 'Choose your ticket', 'Simple choices, transparent inclusions, and a direct path to secure checkout on KonfHub.');
$grid = $add('drupal_india_connect.grid', 'Grid - Ticket cards', ['columns' => 3, 'gap' => '24'], $s, 'main_slot');
foreach ([
  [
    'eyebrow' => 'Complete experience',
    'title' => 'Full Combo',
    'text' => 'One pass for the AI Summit, the conference and the networking experience.',
    'includes' => 'AI Summit (28 Jan) · Conference (29–30 Jan) · Networking',
    'price' => '₹7,000',
    'availability' => $early_bird,
    'cta_label' => 'Choose Full Combo',
    'cta_url' => $link($checkout('118788')),
    'accent' => 'secondary',
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
    'cta_label' => 'Choose this pass',
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
    'cta_label' => 'Choose this pass',
    'cta_url' => $link($checkout('118787')),
    'accent' => 'primary-50',
  ],
  [
    'eyebrow' => 'Early Bird',
    'title' => 'Conference Only',
    'text' => 'For those coming for Drupal, digital experiences, contribution and community.',
    'includes' => 'Conference (29–30 Jan)',
    'excludes' => 'AI Summit and networking not included',
    'benefit' => 'DrupalEasy subscribers: 10% discount',
    'price' => '₹2,500',
    'availability' => $early_bird,
    'cta_label' => 'Choose this pass',
    'cta_url' => $link($checkout('118735')),
    'accent' => 'primary-50',
  ],
  [
    'eyebrow' => 'Single-day experience',
    'title' => 'AI Summit Only',
    'text' => 'A focused day on where AI is creating meaningful change, beyond the hype.',
    'includes' => 'AI Summit (28 Jan)',
    'excludes' => 'Conference and networking not included',
    'price' => '₹5,000',
    'availability' => 'Check current availability on KonfHub',
    'cta_label' => 'Choose this pass',
    'cta_url' => $link($checkout('118733')),
    'accent' => 'primary-50',
  ],
] as $ticket) {
  $add('drupal_india_connect.ticket', "Ticket - {$ticket['title']}", $ticket + ['price_note' => 'per attendee'], $grid, 'grid_slot');
}
$foot = $add('sdc.event_horizon.group', 'Group - Ticket guidance', $group('column', 'sm'), $s, 'footer_slot');
$add('sdc.event_horizon.heading', 'Heading - Not sure which ticket to choose?', [
  'heading_text' => 'Not sure which ticket to choose?',
  'level' => 3,
  'text_size' => 'heading-responsive-xl',
  'text_color' => 'default',
  'align' => 'left',
], $foot, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Ticket guidance', [
  'text' => $html('<p>The Full Combo offers the broadest access. If you are coming mainly for the Drupal conference, Conference Only is the simplest choice. Workshops are booked separately and are not included in any ticket.</p>'),
  'text_size' => 'normal',
  'text_color' => 'default',
], $foot, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Ticket note', [
  'text' => $html('<p>These are the rates currently published for DrupalAsia Connect India 2027. Ticket eligibility, availability, the final total, processing fees and any applicable taxes are confirmed at checkout on KonfHub.</p>'),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $foot, 'group_slot');

// ---------------------------------------------------------------------------
// 4. DrupalEasy subscriber benefit.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - DrupalEasy benefit', $section([
  'columns' => '50-50',
  'section_footer' => TRUE,
]));
$header($s, 'DrupalEasy benefit', 'Subscriber benefit', 'More value with DrupalEasy', 'DrupalEasy subscribers can save on two DrupalAsia Connect India 2027 ticket categories.');
foreach ([
  ['15%', 'AI Summit + Conference', '15% off the AI Summit + Conference combo ticket.'],
  ['10%', 'Conference Only', '10% off the Conference-only ticket.'],
] as [$marker, $title, $body]) {
  $add('drupal_india_connect.info-card', "Info card - DrupalEasy $marker", [
    'marker' => $marker,
    'pill' => 'DrupalEasy subscribers',
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => 'primary',
  ], $s, 'main_slot');
}
$add('sdc.event_horizon.text', 'Text - DrupalEasy terms', [
  'text' => $html('<p>The DrupalEasy subscriber benefit applies only to the two ticket categories above. Coupon details will be shared before or during the event. The ticket prices shown do not include these discounts.</p>'),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 5. Programme at a glance.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Programme at a glance', $section([
  'columns' => '33-33-33',
  'background_color' => 'accent',
  'section_footer' => TRUE,
]));
$header($s, 'Programme at a glance', 'Programme at a glance', 'Imagine. Discover. Build.', 'The event moves from new possibilities to practical knowledge to collaborative action.');
foreach ([
  [1, 'Jan 28', 'Imagine', 'AI Summit', 'Explore AI across enterprise, government, education and the open ecosystem through practitioner-led perspectives.', 'imagine'],
  [2, 'Jan 29', 'Discover', 'Drupal Conference', 'Keynotes, Drupal and Drupal AI sessions, technical conversations, and the future of digital experiences.', 'discover'],
  [3, 'Jan 30', 'Build', 'Contribution & Innovation', 'Contribute, collaborate, mentor and take ideas toward working solutions with the community.', 'build'],
] as [$day, $date, $title, $subtitle, $body, $theme]) {
  $add('drupal_india_connect.program-day-card', "Programme day - $title", [
    'day_number' => $day,
    'date' => $date,
    'title' => $title,
    'subtitle' => $subtitle,
    'description' => $html("<p>$body</p>"),
    'track_theme' => $theme,
  ], $s, 'main_slot');
}
$add('sdc.event_horizon.button', 'Button - Explore the full programme', $button('Explore the full programme', 'internal:/program-at-a-glance', 'secondary'), $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 6. Travel & Stay.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Plan your trip', $section([
  'columns' => '50-50',
  'section_footer' => TRUE,
]));
$header($s, 'Plan your trip', 'Your visit, made easier', 'Plan your trip to Mumbai', 'IIT Bombay is the gathering place. Get practical help with travel, arrival, accommodation and campus access before you book.');
$add('drupal_india_connect.info-card', 'Info card - Victor Menezes Convention Centre', [
  'icon' => 'map-pin',
  'pill' => 'Venue · Powai, Mumbai',
  'title' => 'Victor Menezes Convention Centre',
  'body' => $html('<p><strong>Indian Institute of Technology Bombay<br>IIT Area, Powai, Mumbai, Maharashtra 400076</strong></p><p>Room details will be confirmed closer to the event.</p>'),
  'tbc' => TRUE,
  'accent' => 'primary',
  'link_label' => 'Open in Google Maps',
  'link_url' => $link('https://www.google.com/maps/search/?api=1&query=Victor+Menezes+Convention+Centre%2C+4WM8%2B2XJ%2C+IIT+Area%2C+Powai%2C+Mumbai%2C+Maharashtra+400076'),
], $s, 'main_slot');
$tips = $add('sdc.event_horizon.group', 'Group - Travel tips', $group('column', 'md', ['equal_children' => TRUE]), $s, 'main_slot');
foreach ([
  ['airplane-tilt', 'Arriving by air', 'Fly into Mumbai (BOM), then continue to the campus by cab or public transport.', FALSE],
  ['map-pin', 'Stay near campus', 'Browse accommodation in Powai, Vikhroli and the surrounding neighbourhoods.', FALSE],
  ['check', 'Campus entry', 'Carry your registration confirmation and a government-issued photo ID. Entry details will be shared before the event.', TRUE],
] as [$icon, $title, $body, $tbc]) {
  $add('drupal_india_connect.info-card', "Info card - $title", [
    'icon' => $icon,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'tbc' => $tbc,
    'accent' => 'primary',
  ], $tips, 'group_slot');
}
$add('sdc.event_horizon.button', 'Button - Plan travel & accommodation', $button('Plan travel & accommodation', 'internal:/travel-and-stay'), $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 7. Before you register (FAQ).
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
  ['When do Early Bird prices end?', '<p>Early Bird prices run until 23 October 2026, 11:59 PM IST, or until sold out. Check KonfHub for the current status.</p>', FALSE],
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

// ---------------------------------------------------------------------------
// 8. Registration Team. No people cards until the registration organisers
// confirm who is on it (issue #111); add a people-card grid to main_slot then,
// following the AI Summit page's "Voices of the frontier" section.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Registration Team', $section());
$header($s, 'Registration Team', 'The volunteers behind the experience', 'Meet the Registration Team', 'The registration experience is supported by our community volunteers. Registration team profiles will be featured here once their assignments are confirmed.');
$team = $add('sdc.event_horizon.group', 'Group - Registration Team buttons', $group('row', 'sm'), $s, 'main_slot');
$add('sdc.event_horizon.button', 'Button - Meet the event team', $button('Meet the event team', 'internal:/meet-the-team', 'secondary'), $team, 'group_slot');

// ---------------------------------------------------------------------------
// 9. Closing CTA.
// ---------------------------------------------------------------------------
$cta = $add('sdc.event_horizon.cta', 'CTA - See you in Mumbai', [
  'heading_text' => 'See you in Mumbai',
  'level' => 2,
  'text' => 'Choose your experience, plan your visit and join the conversations shaping what comes next for Drupal, AI and the open web.',
  'text_align' => 'center',
  'background_color' => 'secondary',
  'overlay_opacity' => '0%',
]);
$add('sdc.event_horizon.button', 'Button - Get your ticket on KonfHub', $button('Get your ticket on KonfHub', $konfhub, 'primary-inverted'), $cta, 'actions');

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
