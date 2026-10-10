<?php

/**
 * @file
 * Builds the Travel & Stay canvas_page (issue #75).
 *
 * Run: ddev drush php:script scripts/build-travel-page.php
 *
 * Creates the page unpublished at /travel-and-stay; publish it by hand after
 * review. If a page already has that alias the script stops, because
 * rebuilding replaces every component row and would discard editor changes.
 * Pass `rebuild` to do that deliberately (local only):
 *   ddev drush php:script scripts/build-travel-page.php rebuild
 *
 * Deploys are code-only, so this has to be run against each environment's
 * database after the release carrying the new components has shipped.
 * Back up the database first. Copy is taken from the approved prototype
 * (drupalasia-travel.html), minus its emoji.
 */

use Drupal\Component\Uuid\Php as UuidGen;

$alias = '/travel-and-stay';
$page_url = 'internal:' . $alias;

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
$add('drupal_india_connect.eyebrow', 'Eyebrow - Venue & travel', ['text' => 'Venue & travel', 'tone' => 'on-dark'], $g, 'group_slot');
$add('sdc.event_horizon.heading', 'Heading - DrupalAsia Connect India 2027', [
  'heading_text' => 'DrupalAsia Connect India 2027',
  'level' => 1,
  'text_size' => 'heading-responsive-5xl',
  'text_color' => 'inverted',
  'align' => 'left',
], $g, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hero intro', [
  'text' => $html('<p>Plan your journey to IIT Bombay in Powai, Mumbai: the nearest airport, how to get to campus, and where to stay close to the venue.</p>'),
  'text_size' => 'text-lg',
  'text_color' => 'inverted',
], $g, 'group_slot');
$facts = $add('sdc.event_horizon.group', 'Group - Hero facts', $group('row', 'lg', ['equal_children' => TRUE]), $g, 'group_slot');
foreach ([
  ['28–30 January 2027', 'Dates', 'primary'],
  ['IIT Bombay, Mumbai', 'Location', 'primary'],
  ['Mumbai (BOM)', 'Nearest airport', 'secondary'],
] as [$title, $caption, $accent]) {
  $add('drupal_india_connect.fact', "Fact - $caption", [
    'title' => $caption,
    'text' => $title,
    'accent' => $accent,
    'layout' => 'stack',
    'tone' => 'on-dark',
  ], $facts, 'group_slot');
}
$buttons = $add('sdc.event_horizon.group', 'Group - Hero buttons', $group('row', 'sm'), $g, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Getting there', $button('Getting there', "$page_url#reach", 'primary-inverted'), $buttons, 'group_slot');
$add('sdc.event_horizon.button', 'Button - Where to stay', $button('Where to stay', "$page_url#stay", 'primary'), $buttons, 'group_slot');

// ---------------------------------------------------------------------------
// 2. Venue.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Venue', $section(['columns' => '50-50']));
$header($s, 'Venue', 'Venue', 'IIT Bombay, Powai', 'A green campus beside Powai Lake in north-east Mumbai, hosting all three days of sessions, workshops and contribution sprints.');
$add('drupal_india_connect.info-card', 'Info card - Victor Menezes Convention Centre', [
  'icon' => 'map-pin',
  'title' => 'Victor Menezes Convention Centre',
  'body' => $html('<p><strong>Indian Institute of Technology Bombay<br>4WM8+2XJ, IIT Area, Powai,<br>Mumbai, Maharashtra 400076</strong></p><p>Room details for each track will be shared closer to the event.</p>'),
  'tbc' => TRUE,
  'accent' => 'primary',
  'link_label' => 'Open in Google Maps',
  'link_url' => $link('https://www.google.com/maps/search/?api=1&query=Victor+Menezes+Convention+Centre%2C+4WM8%2B2XJ%2C+IIT+Area%2C+Powai%2C+Mumbai%2C+Maharashtra+400076'),
], $s, 'main_slot');
$days = $add('sdc.event_horizon.group', 'Group - Venue days', $group('column', 'md', ['equal_children' => TRUE]), $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Day 1 AI Summit', [
  'pill' => 'Day 1 · Imagine',
  'title' => 'AI Summit',
  'body' => $html('<p>Thursday 28 January. A dedicated day for Drupal, AI and the open web.</p>'),
  'accent' => 'primary',
], $days, 'group_slot');
$add('drupal_india_connect.info-card', 'Info card - Day 2 & 3 Conference', [
  'pill' => 'Day 2 & 3 · Discover',
  'title' => 'Conference',
  'body' => $html('<p>Friday 29 – Saturday 30 January. Sessions, keynotes, community spaces and contribution.</p>'),
  'accent' => 'primary-dark',
], $days, 'group_slot');

// ---------------------------------------------------------------------------
// 3. Getting there.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Getting there', $section([
  'columns' => '33-33-33',
  'background_color' => 'accent',
  'section_footer' => TRUE,
]));
$header($s, 'Getting there', 'Getting there', 'Nearest airport & transfers', "Mumbai's airport is close to Powai, but traffic decides the journey time. Allow a buffer on arrival and departure days.");
$add('drupal_india_connect.info-card', 'Info card - Airport', [
  'icon' => 'airplane-tilt',
  'pill' => 'Nearest airport',
  'title' => 'Chhatrapati Shivaji Maharaj Intl. Airport (BOM)',
  'body' => $html('<p>International flights use <strong>Terminal 2 (Sahar)</strong>, about 10 km from campus. Domestic flights use <strong>Terminal 1 (Santa Cruz)</strong>, about 15 km away.</p>'),
  'accent' => 'primary',
], $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Railway stations', [
  'icon' => 'train',
  'title' => 'Nearest railway stations',
  'body' => $html('<p><strong>Kanjurmarg</strong> (Central Line) is about 3 km away, <strong>Vikhroli</strong> and <strong>Ghatkopar</strong> are also close. On the Western Line, use <strong>Andheri</strong> (about 10 km).</p><p>From the station, an auto-rickshaw or cab takes roughly 10–15 minutes.</p>'),
  'accent' => 'primary',
], $s, 'main_slot');
$add('drupal_india_connect.info-card', 'Info card - Metro', [
  'icon' => 'subway',
  'title' => 'Metro',
  'body' => $html('<p>Mumbai Metro <strong>Line 3 (Aqua)</strong> from the airport stations to <strong>Aarey JVLR</strong>, or <strong>Line 1</strong> from Airport Road to <strong>Saki Naka</strong>. Then take an auto or cab to campus.</p><p>Check live timings before travelling.</p>'),
  'accent' => 'primary',
], $s, 'main_slot');
$foot = $add('sdc.event_horizon.group', 'Group - Transfers', $group('column', 'md', ['equal_children' => TRUE]), $s, 'footer_slot');
$add('sdc.event_horizon.heading', 'Heading - Airport to IIT Bombay', [
  'heading_text' => 'Airport to IIT Bombay',
  'level' => 3,
  'text_size' => 'heading-responsive-xl',
  'text_color' => 'default',
  'align' => 'left',
], $foot, 'group_slot');
$table = $add('drupal_india_connect.data-table', 'Data table - Airport transfers', [
  'caption' => 'Ways to get from the airport to IIT Bombay',
  'column_1' => 'Option',
  'column_2' => 'Typical time',
  'column_3' => 'Approx. cost',
  'column_4' => 'Notes',
], $foot, 'group_slot');
foreach ([
  ['Ride-hailing (Uber, Ola)', '25–45 min', '₹300–₹550', 'Easiest option. Book in the app after landing, pickup points are signposted at both terminals.'],
  ['Prepaid taxi', '25–45 min', 'Fixed fare at counter', 'Counters sit outside arrivals. Say "IIT Bombay, Powai".'],
  ['Metro + auto', '45–70 min', 'Low', 'Best when road traffic is heavy. Last leg is a short auto ride.'],
  ['BEST bus', 'Varies', 'Lowest', 'Routes serve Powai and the IIT gates. Use the Chalo app for current routes.'],
] as [$a, $b, $c, $d]) {
  $add('drupal_india_connect.data-table-row', "Row - $a", ['cell_1' => $a, 'cell_2' => $b, 'cell_3' => $c, 'cell_4' => $d], $table, 'rows');
}
$add('sdc.event_horizon.text', 'Text - Transfer note', [
  'text' => $html("<p>Times and fares are indicative and vary with traffic and time of day. Tell your driver <em>IIT Bombay, Powai</em> and confirm the gate (Main Gate or Market Gate) so you don't end up at another campus with a similar name.</p>"),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $foot, 'group_slot');

// ---------------------------------------------------------------------------
// 4. Where to stay.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Where to stay', $section([
  'columns' => '33-33-33',
  'section_footer' => TRUE,
]));
$header($s, 'Where to stay', 'Where to stay', 'Hotels near IIT Bombay', 'Hotels and serviced apartments in and around Powai, sorted by distance from the venue. Book directly with the property.');
foreach ([
  ['Rodas An Ecotel Boutique Hotel, Powai', 'Central Ave, Hiranandani Gardens, Powai, Mumbai 400076', '2.5', 'https://maps.app.goo.gl/qM5bA9d1nZN7Dpcq9'],
  ['Meluha The Fern Mumbai, Powai, Series by Marriott', 'Central Avenue, Hiranandani Gardens, Powai, 400076 Mumbai, India', '2.6', 'https://maps.app.goo.gl/6qz5ZYamhowD5aPf9'],
  ['The Beatle Hotel', 'JMJ House, Orchard Ave, Hiranandani Gardens, Powai, Mumbai 400076', '3', 'https://maps.app.goo.gl/Y3rsmnMc6waRrc9E9'],
  ['Zenia Luxury Suites & Serviced Apartments', 'Regent Hill, Hiranandani Gardens, Powai, Mumbai 400076', '3.2', 'https://maps.app.goo.gl/RhaGwhEcDFcp8vYs6'],
  ['ibis Mumbai Vikhroli', 'CTS No 17, LBS Marg, Chandan Nagar, Vikhroli West, Mumbai 400083', '3.3', 'https://maps.app.goo.gl/jfmh4NuEgLTZwEQaA'],
  ['RELOhomes Serviced Apartment', 'Hiranandani Gardens, Powai B Wing, Regent Hill, Powai, 400076 Mumbai, India', '3.7', 'https://maps.app.goo.gl/EV4ARhUKJPa16BpR7'],
  ['Lakeside Chalet - Marriott Executive Apartments', 'Kailash Nagar, Mayur Nagar, Powai, Mumbai 400087', '5.4', 'https://maps.app.goo.gl/62SdjSYivK43Rq917'],
  ['Renaissance Mumbai Hotel & Convention Centre', 'Kailash Nagar, Morarji Nagar, Powai, Mumbai 400087', '5.7', 'https://maps.app.goo.gl/dTEKnTvfXoES9ZTH8'],
] as [$name, $address, $km, $map]) {
  $add('drupal_india_connect.place-card', "Hotel - $name", [
    'name' => $name,
    'distance' => "~$km km",
    'address' => $address,
    'variant' => 'place',
    'link_label' => 'View on Google Maps',
    'link_url' => $link($map),
  ], $s, 'main_slot');
}
$foot = $add('sdc.event_horizon.group', 'Group - Booking links', $group('column', 'md', ['equal_children' => TRUE]), $s, 'footer_slot');
$grid = $add('drupal_india_connect.grid', 'Grid - Booking sites', ['columns' => 3, 'gap' => '24'], $foot, 'group_slot');
foreach ([
  ['Hotels on Booking.com', 'Browse stays near the venue, sorted by popularity.', 'Browse Booking.com', 'https://www.booking.com/searchresults.en-gb.html?dest_id=199716&dest_type=landmark&order=popularity'],
  ['Budget stays on OYO', 'Browse OYO hotels for DrupalCon Mumbai.', 'Browse OYO', 'https://www.oyorooms.com/hotels-in-mumbai-drupalcon-mumbai/'],
  ['Prefer a home stay?', 'Browse Airbnb listings in Powai, Mumbai.', 'Search Airbnb', 'https://www.airbnb.co.in/s/Powai--Mumbai--India/homes?adults=1&refinement_paths%5B%5D=%2Fhomes&place_id=ChIJQ3sdHAjI5zsRZuOLS8UA8bo&location=Powai%2C+Mumbai%2C+India'],
] as [$name, $desc, $label, $url]) {
  $add('drupal_india_connect.place-card', "Booking link - $name", [
    'name' => $name,
    'description' => $desc,
    'variant' => 'link',
    'link_label' => $label,
    'link_url' => $link($url),
  ], $grid, 'grid_slot');
}
$note = $add('sdc.event_horizon.group', 'Group - Hotel note', $group('row', 'sm', ['items_align' => 'center']), $foot, 'group_slot');
$add('sdc.event_horizon.text', 'Text - Hotel note', [
  'text' => $html('<p>Distances are approximate, measured from each property to IIT Bombay. Listings are suggestions, not endorsements, and no hotel block or discount has been arranged yet.</p>'),
  'text_size' => 'text-sm',
  'text_color' => 'default',
], $note, 'group_slot');
$add('drupal_india_connect.tbc-tag', 'TBC - Hotel block', ['label' => 'TBC'], $note, 'group_slot');

// ---------------------------------------------------------------------------
// 5. Plan your trip.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - Plan your trip', $section([
  'columns' => '33-33-33',
  'background_color' => 'accent',
]));
$header($s, 'Plan your trip', 'Plan your trip', 'Before you fly', 'A short checklist for first-time visitors to Mumbai.');
foreach ([
  ['01', 'Visa', '<p>Visitors from outside India generally need a visa. Many nationalities can apply for an e-Visa (including an e-Conference category) on the official portal <a href="https://indianvisaonline.gov.in/evisa/">indianvisaonline.gov.in</a>. Eligibility and rules change, so check the portal for your nationality and apply well ahead.</p><p>Whether DrupalAsia Connect will issue invitation letters will be announced.</p>', TRUE],
  ['02', 'Weather in January', '<p>Mumbai is typically dry and sunny in late January, with warm days and mild evenings. Pack light cotton clothes, a thin layer for air-conditioned halls, sunscreen and comfortable walking shoes for the campus.</p>', FALSE],
  ['03', 'Money & connectivity', '<p>ATMs are widely available and cards are accepted in most hotels. UPI is the everyday payment method for locals, though foreign cards or cash work for most visitors. Airport counters sell tourist SIMs and eSIMs are common.</p>', FALSE],
  ['04', 'Traffic', '<p>Peak hours on the Eastern Express Highway and JVLR are roughly mornings and early evenings on weekdays. Day 1 is a Thursday, so leave extra time if you stay outside Powai.</p>', FALSE],
  ['05', 'Campus entry', '<p>IIT Bombay is a secured campus. Carry a government photo ID and your registration confirmation. Entry details will be shared before the event.</p>', TRUE],
  ['06', 'Emergencies', '<p>Dial <strong>112</strong> anywhere in India for police, fire and ambulance. Share your hotel address and travel plans with a friend or colleague.</p>', FALSE],
] as [$num, $title, $body, $tbc]) {
  $add('drupal_india_connect.info-card', "Info card - $num $title", [
    'marker' => $num,
    'title' => $title,
    'body' => $html($body),
    'tbc' => $tbc,
    'accent' => $num === '06' ? 'secondary' : 'primary',
  ], $s, 'main_slot');
}

// ---------------------------------------------------------------------------
// 6. FAQ.
// ---------------------------------------------------------------------------
$s = $add('sdc.event_horizon.section', 'Section - FAQ', $section(['width' => '75%', 'full_width' => TRUE]));
$header($s, 'FAQ', 'FAQ', 'Quick answers', 'Still unsure? These are the questions we expect most.');
$acc = $add('sdc.event_horizon.accordion-container', 'Accordion container - FAQ', [], $s, 'main_slot');
foreach ([
  ['Which airport should I fly into?', "<p>Mumbai's Chhatrapati Shivaji Maharaj International Airport (BOM). International flights land at Terminal 2, domestic flights at Terminal 1.</p>", FALSE],
  ['How far is the venue from the nearest hotels?', '<p>The closest options on this page are about 2.5 to 3.3 km from IIT Bombay. Others are up to around 5.7 km away.</p>', FALSE],
  ['Are autos and cabs easy to find?', '<p>Yes. Ride-hailing apps work across Mumbai, and auto-rickshaws are available near the campus gates and stations. Surge pricing can apply at peak times.</p>', FALSE],
  ['Is there an official hotel block or discount?', '<p>None has been announced yet. We will update this page if that changes.</p>', TRUE],
  ['When do tickets open?', '<p>Tickets are coming soon. Follow <a href="/announcements">key dates and announcements</a> for updates.</p>', FALSE],
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
// 7. CTA.
// ---------------------------------------------------------------------------
$cta = $add('sdc.event_horizon.cta', 'CTA - See you in Mumbai', [
  'heading_text' => 'See you in Mumbai',
  'level' => 2,
  'text' => 'Get updates on tickets, the programme and travel details.',
  'text_align' => 'center',
  'background_color' => 'secondary',
  'overlay_opacity' => '0%',
]);
$add('sdc.event_horizon.button', 'Button - Get event updates', $button('Get event updates', 'internal:/announcements', 'primary-inverted'), $cta, 'actions');

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
    'title' => 'Travel & Stay',
    'description' => 'Plan your trip to DrupalAsia Connect India 2027 at IIT Bombay, Powai, Mumbai: nearest airport, getting to the venue, hotels and travel tips.',
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
$page->setRevisionLogMessage('Build Travel & Stay page (issue #75).');
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
