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
 * hero-split, event-days and callout, and the ticket (`excludes`, `benefit`,
 * `featured`, `badge`, `anchor`), countdown (`strip`), venue-location
 * (`panel`) and info-card (`marker_suffix`) options. Back up the database
 * first.
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
// 1. Hero: copy, buttons and the three facts beside the navy "three days"
// panel, then the Early Bird strip.
// ---------------------------------------------------------------------------
$hero = $add('drupal_india_connect.hero-split', 'Hero - Tickets & registration', [
  'eyebrow' => 'Tickets & registration',
  'title' => 'Choose your experience.',
  'title_highlight' => "Be part of what's next.",
  'lede' => "Three days to imagine new possibilities, discover what's being built and build together with the Drupal and open-source community.",
  'primary_label' => 'Buy tickets',
  'primary_url' => $link($konfhub),
  'secondary_label' => 'Compare tickets',
  'secondary_url' => $link('#tickets'),
]);
$facts = $add('drupal_india_connect.grid', 'Grid - Hero facts', ['columns' => 3, 'gap' => '16'], $hero, 'facts');
foreach ([
  ['Dates', '28–30 January 2027', 'primary'],
  ['Tickets from', '₹2,500 per attendee', 'primary'],
  ['Early Bird', 'Until 23 October 2026, or until sold out', 'secondary'],
] as [$caption, $text, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $caption", [
    'title' => $caption,
    'text' => $text,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'plain',
  ], $facts, 'grid_slot');
}
$add('drupal_india_connect.event-days', 'Event days - Three days', [
  'label' => 'Imagine · Discover · Build',
  'title' => 'Three days. One connected',
  'title_highlight' => 'community.',
  'day_1_date' => '28 Jan',
  'day_1_title' => 'Imagine',
  'day_1_subtitle' => 'AI Summit',
  'day_2_date' => '29 Jan',
  'day_2_title' => 'Discover',
  'day_2_subtitle' => 'Drupal Conference',
  'day_3_date' => '30 Jan',
  'day_3_title' => 'Build',
  'day_3_subtitle' => 'Contribution & Innovation',
], $hero, 'aside');

// Counts down to the Early Bird cutoff and swaps to the finished text after it.
$add('drupal_india_connect.countdown', 'Countdown - Early Bird ends', [
  'target' => '2026-10-23T23:59:00+05:30',
  'eyebrow' => 'Early Bird ends',
  'label' => '23 October 2026, 11:59 PM IST',
  'finished_text' => 'Early Bird has ended. Check KonfHub for current prices.',
  'variant' => 'strip',
  'link_label' => 'See Early Bird tickets',
  'link_url' => $link('#tickets'),
]);

// ---------------------------------------------------------------------------
// 2. Choose your experience.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Find your fit', $section([
  'columns' => '33-33-33',
]));
$header($s, 'Find your fit', 'Find your fit', 'One event. Different ways to join.', 'Whether AI is your focus, Drupal is your everyday work, or you want the entire experience, choose the pass that matches your plans.');
foreach ([
  ['01', 'Imagine', 'Explore AI', 'A dedicated summit connecting practical AI applications, decision-makers and the open ecosystem.', 'See AI Summit options', '#ticket-ai-summit-only'],
  ['02', 'Discover', 'Go deeper with Drupal', 'Conference sessions, ideas and real-world lessons across Drupal, digital experiences and the open web.', 'See conference passes', '#ticket-conference-only'],
  ['03', 'Connect', 'Make more of the trip', 'Add networking to spend more time with the practitioners and people shaping the ecosystem.', 'Explore combination passes', '#ticket-full-combo'],
] as [$num, $pill, $title, $body, $cta, $target]) {
  $add('drupal_india_connect.info-card', "Info card - $num $title", [
    'marker' => $num,
    'pill' => $pill,
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => $num === '03' ? 'secondary' : 'primary',
    'link_label' => $cta,
    'link_url' => $link($target),
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 3. Compare tickets: Full Combo featured on its own, the other four across,
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
$add('drupal_india_connect.callout', 'Callout - Not sure which ticket to choose?', [
  'heading' => 'Not sure which ticket to choose?',
  'text' => 'The Full Combo offers the broadest access. If you are coming mainly for the Drupal conference, Conference Only is the simplest choice. Workshops are booked separately and are not included in any ticket.',
  'note' => 'These are the rates currently published for DrupalAsia Connect India 2027. Ticket eligibility, availability, the final total, processing fees and any applicable taxes are confirmed at checkout on KonfHub.',
  'cta_label' => 'View all options on KonfHub',
  'cta_url' => $link($konfhub),
  'style' => 'solid',
], $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 4. DrupalEasy subscriber benefit.
// ---------------------------------------------------------------------------
// Prototype layout: copy on the left, the two offers as tiles on the right,
// stacked below 1000px (a section's 50-50 splits from 700px, which squeezes
// the two tiles).
$s = $add('sdc.event_horizon.section', 'Section - DrupalEasy benefit', $section([
  'section_header' => FALSE,
]));
$split = $add('drupal_india_connect.grid', 'Grid - DrupalEasy layout', ['columns' => 2, 'gap' => '48', 'tablet_columns' => '1'], $s, 'main_slot');
$intro = $add('sdc.event_horizon.group', 'Group - DrupalEasy copy', $group('column', 'sm'), $split, 'grid_slot');
$add('drupal_india_connect.eyebrow', 'Eyebrow - Subscriber benefit', ['text' => 'Subscriber benefit', 'tone' => 'accent'], $intro, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - More value with DrupalEasy', [
  'heading_text' => 'More value with DrupalEasy',
  'level' => 2,
  'text_size' => 'heading-responsive-3xl',
  'text_color' => 'default',
  'align' => 'left',
], $intro, 'group_slot');
$add('sdc.event_horizon.text', 'Text - DrupalEasy intro', [
  'text' => $html('<p>DrupalEasy subscribers can save on two DrupalAsia Connect India 2027 ticket categories.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'default',
], $intro, 'group_slot');
$add('sdc.event_horizon.text', 'Text - DrupalEasy terms', [
  'text' => $html('<p>The benefit applies only to these two ticket categories. Coupon details will be shared before or during the event. The ticket prices shown do not include these discounts.</p>'),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $intro, 'group_slot');
$offers = $add('drupal_india_connect.grid', 'Grid - DrupalEasy offers', ['columns' => 2, 'gap' => '24'], $split, 'grid_slot');
foreach ([
  ['15%', 'AI Summit + Conference', 'On the AI Summit + Conference combo ticket.'],
  ['10%', 'Conference Only', 'On the Conference-only ticket.'],
] as [$marker, $title, $body]) {
  $add('drupal_india_connect.info-card', "Info card - DrupalEasy $marker off", [
    'marker' => $marker,
    'marker_suffix' => 'off',
    'title' => $title,
    'body' => $html("<p>$body</p>"),
    'accent' => 'secondary',
  ], $offers, 'grid_slot');
}

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
  [1, 'Thursday 28 January', 'Imagine', 'AI Summit', 'Explore AI across enterprise, government, education and the open ecosystem through practitioner-led perspectives.', 'imagine'],
  [2, 'Friday 29 January', 'Discover', 'Drupal Conference', 'Keynotes, Drupal and Drupal AI sessions, technical conversations, and the future of digital experiences.', 'discover'],
  [3, 'Saturday 30 January', 'Build', 'Contribution & Innovation', 'Contribute, collaborate, mentor and take ideas toward working solutions with the community.', 'build'],
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
$add('sdc.event_horizon.button', 'Button - Explore the full programme', $button('Explore the full programme', 'internal:/program-at-a-glance', 'secondary-inverted'), $s, 'footer_slot');

// ---------------------------------------------------------------------------
// 6. Travel & Stay.
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
  ], $tips, 'grid_slot');
}

// ---------------------------------------------------------------------------
// 7. Before you register (FAQ).
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Before you register', $section([
  'columns' => '67-33',
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
$add('drupal_india_connect.callout', 'Callout - The people behind DrupalAsia Connect', [
  'icon' => 'users-three',
  'heading' => 'The people behind DrupalAsia Connect',
  'text' => 'Meet the organisers and volunteers across every workstream on the Meet the Team page.',
  'cta_label' => 'Meet the event team',
  'cta_url' => $link('internal:/meet-the-team'),
  'style' => 'dashed',
], $s, 'main_slot');

// ---------------------------------------------------------------------------
// 9. Closing CTA.
// ---------------------------------------------------------------------------
$add('drupal_india_connect.closing-cta', 'Closing CTA - Your seat in the community awaits', [
  'eyebrow' => 'See you in Mumbai',
  'heading' => 'Your seat in the community awaits.',
  'text' => 'Choose your experience, plan your visit and join the conversations shaping what comes next for Drupal, AI and the open web.',
  'primary_label' => 'Get your ticket on KonfHub',
  'primary_url' => $link($konfhub),
  'secondary_label' => 'Plan your visit',
  'secondary_url' => $link('internal:/travel-and-stay'),
  'stub_title' => 'DrupalAsia Connect India 2027',
  'detail_1_label' => 'Dates',
  'detail_1_value' => '28–30 January 2027',
  'detail_2_label' => 'Venue',
  'detail_2_value' => 'IIT Bombay, Powai, Mumbai',
  'detail_3_label' => 'Tickets from',
  'detail_3_value' => '₹2,500 per attendee',
  'detail_4_label' => 'Early Bird',
  'detail_4_value' => 'Until 23 October 2026',
  'stub_note' => 'Secure checkout on KonfHub',
]);

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
