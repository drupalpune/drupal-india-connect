<?php

/**
 * @file
 * Rebuilds the two panel-discussion pages from the approved prototypes
 * (docs/panel-revamp/index.html and day2.html), in one shared layout:
 *
 *   /ai-summit/building-intelligent-public-infrastructure   (Day 1)
 *   /conference/whats-next-for-drupal                       (Day 2)
 *
 * Run (replaces every component row on the page, so it asks for `rebuild`):
 *   ddev drush php:script scripts/build-panel-pages.php rebuild
 *   ddev drush php:script scripts/build-panel-pages.php rebuild day1
 *   ddev drush php:script scripts/build-panel-pages.php rebuild day2
 *
 * Needs the release carrying grid's `variant` and info-card's `style` props.
 * Deploys are code-only: run this against each environment's database after
 * that release ships, after backing the database up. Pages are found by
 * alias, not id, so ids may differ between environments. The moderator's
 * People Card is carried over from the page as it stands, so its photo
 * reference is that environment's own media entity.
 */

use Drupal\Component\Uuid\Php as UuidGen;

if (($extra[0] ?? '') !== 'rebuild') {
  echo "This replaces every component row on the panel pages. Pass 'rebuild' to go ahead.\n";
  return;
}
$only = $extra[1] ?? NULL;

$etm = \Drupal::entityTypeManager();
$component_storage = $etm->getStorage('component');
$page_storage = $etm->getStorage('canvas_page');
$alias_manager = \Drupal::service('path_alias.manager');
$uuid_gen = new UuidGen();

$html = fn(string $value) => ['value' => $value, 'format' => 'canvas_html_block'];

$section = fn(array $overrides = []) => $overrides + [
  'width' => '100%',
  'columns' => '100',
  'mobile_columns' => '1',
  'margin_block_start' => '0',
  'margin_block_end' => '0',
  'padding_block_start' => '64',
  'padding_block_end' => '64',
  'section_header' => FALSE,
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

$button = fn(string $label, string $uri, string $variant, ?string $icon = NULL) => [
  'variant' => $variant,
  'label' => $label,
  'href' => ['uri' => $uri, 'options' => []],
  'size' => 'medium',
  'mobile_width' => FALSE,
  'icon_first' => FALSE,
  'disabled' => FALSE,
] + ($icon ? ['icon' => $icon] : []);

$heading = fn(string $text, bool $dark = FALSE) => [
  'heading_text' => $text,
  'level' => 2,
  'text_size' => 'heading-responsive-3xl',
  'text_color' => $dark ? 'inverted' : 'default',
  'align' => 'left',
];

$text = fn(string $value, string $size = 'normal', bool $dark = FALSE) => [
  'text' => $html("<p>$value</p>"),
  'text_size' => $size,
  'text_color' => $dark ? 'inverted' : 'default',
];

/**
 * Builds one page's rows from its content spec.
 */
$build = function (array $c) use ($component_storage, $uuid_gen, $section, $group, $button, $heading, $text, $html): array {
  $rows = [];
  // Adds a row and returns its uuid.
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

  // 1. Hero: title, intro and actions; session details beside; jump links.
  $s = $add('sdc.event_horizon.section', 'Section - Hero', $section([
    'columns' => '67-33',
    'grid_align' => 'start',
    'background_color' => 'black',
    'dark_background' => TRUE,
    'section_footer' => TRUE,
  ]));
  $g = $add('sdc.event_horizon.group', 'Group - Hero', $group('column', 'lg'), $s, 'main_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - {$c['eyebrow']}", ['text' => $c['eyebrow'], 'tone' => 'on-dark'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$c['title']}", [
    'heading_text' => $c['title'],
    'level' => 1,
    'text_size' => 'heading-responsive-5xl',
    'text_color' => 'inverted',
    'align' => 'left',
  ], $g, 'group_slot');
  if (!empty($c['subtitle'])) {
    $add('sdc.event_horizon.text', 'Text - Hero subtitle', $text($c['subtitle'], 'text-2xl', TRUE), $g, 'group_slot');
  }
  $add('sdc.event_horizon.text', 'Text - Hero intro', $text($c['intro'], 'text-lg', TRUE), $g, 'group_slot');
  $actions = $add('sdc.event_horizon.group', 'Group - Hero actions', $group('row', 'sm'), $g, 'group_slot');
  $add('sdc.event_horizon.button', 'Button - Get tickets', $button('Get tickets', 'internal:/#tickets', 'primary', 'arrow-right'), $actions, 'group_slot');
  $add('sdc.event_horizon.button', "Button - {$c['second_cta'][0]}", $button($c['second_cta'][0], $c['second_cta'][1], 'secondary-inverted'), $actions, 'group_slot');

  $side = $add('sdc.event_horizon.group', 'Group - At a glance', $group('column', 'sm'), $s, 'main_slot');
  $add('drupal_india_connect.eyebrow', 'Eyebrow - At a glance', ['text' => 'At a glance', 'tone' => 'on-dark'], $side, 'group_slot');
  $panel = $add('drupal_india_connect.grid', 'Grid - Session details', ['columns' => 1, 'gap' => '0', 'variant' => 'panel'], $side, 'group_slot');
  foreach ($c['glance'] as [$label, $value]) {
    $add('drupal_india_connect.fact', "Fact - $label", [
      'title' => $label,
      'text' => $value,
      'accent' => 'primary',
      'layout' => 'inline',
      'tone' => 'on-dark',
    ], $panel, 'grid_slot');
  }
  $nav = ['label' => 'On this page'];
  foreach ($c['jump'] as $i => [$label, $anchor]) {
    $nav['link_' . ($i + 1) . '_label'] = $label;
    $nav['link_' . ($i + 1) . '_anchor'] = $anchor;
  }
  $add('drupal_india_connect.anchor-nav', 'Jump links - On this page', $nav, $s, 'footer_slot');

  // 2. The conversation: copy beside a pull quote.
  $cv = $c['conversation'];
  $s = $add('sdc.event_horizon.section', 'Section - The conversation', $section(['columns' => '50-50', 'grid_align' => 'center']));
  $g = $add('sdc.event_horizon.group', 'Group - The conversation', $group('column', 'md'), $s, 'main_slot');
  $add('drupal_india_connect.eyebrow', 'Eyebrow - The conversation', ['text' => 'The conversation', 'tone' => 'accent'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$cv['heading']}", $heading($cv['heading']), $g, 'group_slot');
  foreach ($cv['paragraphs'] as $i => $p) {
    $add('sdc.event_horizon.text', 'Text - Conversation ' . ($i + 1), $text($p, $i === 0 ? 'text-lg' : 'normal'), $g, 'group_slot');
  }
  $add('drupal_india_connect.info-card', 'Info card - Pull quote', [
    'style' => 'quote',
    'title' => $cv['quote'],
    'pill' => $cv['quote_caption'],
    'accent' => 'secondary',
  ], $s, 'main_slot');

  // 3. Joined cards: the parts of the picture, side by side.
  $j = $c['joined'];
  $s = $add('sdc.event_horizon.section', "Section - {$j['heading']}", $section(['background_color' => 'accent', 'section_header' => TRUE]));
  $h = $add('sdc.event_horizon.group', "Group - {$j['heading']} header", $group('column', 'sm'), $s, 'header_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - {$j['eyebrow']}", ['text' => $j['eyebrow'], 'tone' => 'accent'], $h, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$j['heading']}", $heading($j['heading']), $h, 'group_slot');
  $add('sdc.event_horizon.text', "Text - {$j['heading']} intro", $text($j['intro'], 'text-lg'), $h, 'group_slot');
  $grid = $add('drupal_india_connect.grid', "Grid - {$j['heading']}", [
    'columns' => count($j['cards']),
    'gap' => '0',
    'variant' => $j['variant'],
  ], $s, 'main_slot');
  foreach ($j['cards'] as $i => [$role, $title, $body]) {
    $add('drupal_india_connect.info-card', "Info card - $title", [
      'marker' => sprintf('%02d', $i + 1),
      'pill' => $role,
      'title' => $title,
      'body' => $html("<p>$body</p>"),
      'accent' => $i === count($j['cards']) - 1 ? 'secondary' : 'primary',
    ], $grid, 'grid_slot');
  }

  // 4. A scenario on navy: speech bubble, then the steps it raises.
  $sc = $c['scenario'];
  $s = $add('sdc.event_horizon.section', "Section - {$sc['heading']}", $section([
    'columns' => '50-50',
    'grid_align' => 'end',
    'background_color' => 'black',
    'dark_background' => TRUE,
    'section_footer' => TRUE,
  ]));
  $g = $add('sdc.event_horizon.group', "Group - {$sc['heading']}", $group('column', 'md'), $s, 'main_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - {$sc['eyebrow']}", ['text' => $sc['eyebrow'], 'tone' => 'on-dark'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$sc['heading']}", $heading($sc['heading'], TRUE), $g, 'group_slot');
  if (!empty($sc['note'])) {
    $add('sdc.event_horizon.text', 'Text - Scenario note', $text($sc['note'], 'normal', TRUE), $g, 'group_slot');
  }
  $add('drupal_india_connect.info-card', 'Info card - Speech bubble', [
    'style' => 'bubble',
    'icon' => 'user',
    'pill' => $sc['who'],
    'title' => $sc['says'],
    'accent' => 'primary',
  ], $s, 'main_slot');
  $grid = $add('drupal_india_connect.grid', 'Grid - Steps', [
    'columns' => count($sc['steps']),
    'gap' => '24',
    'variant' => 'steps',
  ], $s, 'footer_slot');
  foreach ($sc['steps'] as $i => $step) {
    $inputs = [
      'marker' => sprintf('%02d', $i + 1),
      'title' => $step['title'],
      'accent' => $i === count($sc['steps']) - 1 ? 'secondary' : 'primary',
    ];
    if (!empty($step['pill'])) {
      $inputs['pill'] = $step['pill'];
    }
    if (!empty($step['body'])) {
      $inputs['body'] = $html("<p>{$step['body']}</p>");
    }
    $add('drupal_india_connect.info-card', "Info card - {$step['title']}", $inputs, $grid, 'grid_slot');
  }
  if (!empty($sc['conclusion'])) {
    $add('sdc.event_horizon.text', 'Text - Scenario conclusion', [
      'text' => $html("<p><strong>{$sc['conclusion']}</strong></p>"),
      'text_size' => 'text-lg',
      'text_color' => 'inverted',
    ], $s, 'footer_slot');
  }

  // 5. Questions: heading beside a numbered list.
  $q = $c['questions'];
  $s = $add('sdc.event_horizon.section', 'Section - Questions', $section(['columns' => '33-67', 'grid_align' => 'start']));
  $g = $add('sdc.event_horizon.group', 'Group - Questions header', $group('column', 'sm'), $s, 'main_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - {$q['eyebrow']}", ['text' => $q['eyebrow'], 'tone' => 'accent'], $g, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$q['heading']}", $heading($q['heading']), $g, 'group_slot');
  if (!empty($q['intro'])) {
    $add('sdc.event_horizon.text', 'Text - Questions intro', $text($q['intro'], 'text-lg'), $g, 'group_slot');
  }
  $grid = $add('drupal_india_connect.grid', 'Grid - Questions', ['columns' => 1, 'gap' => '0', 'variant' => 'list'], $s, 'main_slot');
  foreach ($q['items'] as $i => $item) {
    $inputs = [
      'marker' => sprintf('%02d', $i + 1),
      'title' => $item['title'],
      'accent' => $i === count($q['items']) - 1 ? 'secondary' : 'primary',
    ];
    if (!empty($item['pill'])) {
      $inputs['pill'] = $item['pill'];
    }
    if (!empty($item['body'])) {
      $inputs['body'] = $html("<p>{$item['body']}</p>");
    }
    $add('drupal_india_connect.info-card', 'Info card - Question ' . ($i + 1), $inputs, $grid, 'grid_slot');
  }

  // 6. The panel: moderator beside placeholder seats.
  $pn = $c['panel'];
  $s = $add('sdc.event_horizon.section', 'Section - The panel', $section([
    'background_color' => 'accent',
    'section_header' => TRUE,
    'section_footer' => TRUE,
  ]));
  $h = $add('sdc.event_horizon.group', 'Group - The panel header', $group('column', 'sm'), $s, 'header_slot');
  $add('drupal_india_connect.eyebrow', "Eyebrow - {$pn['eyebrow']}", ['text' => $pn['eyebrow'], 'tone' => 'accent'], $h, 'group_slot');
  $add('sdc.event_horizon.heading', "Heading - {$pn['heading']}", $heading($pn['heading']), $h, 'group_slot');
  $add('sdc.event_horizon.text', 'Text - The panel intro', $text($pn['intro'], 'text-lg'), $h, 'group_slot');
  $grid = $add('drupal_india_connect.grid', 'Grid - Panel seats', ['columns' => 5, 'gap' => '16', 'variant' => 'seats'], $s, 'main_slot');
  $add('drupal_india_connect.people-card', "People card - {$pn['moderator']['name']}", $pn['moderator'], $grid, 'grid_slot');
  for ($i = 1; $i <= $pn['seats']; $i++) {
    $add('drupal_india_connect.info-card', "Info card - Panelist seat $i", [
      'icon' => 'user',
      'title' => 'Panelist',
      'body' => $html('<p>To be announced</p>'),
      'accent' => 'primary',
    ], $grid, 'grid_slot');
  }
  $add('sdc.event_horizon.text', 'Text - The panel note', $text($pn['note']), $s, 'footer_slot');

  // 7. Closing band.
  $cl = $c['closing'];
  $s = $add('sdc.event_horizon.section', "Section - CTA {$cl['heading']}", $section([
    'background_color' => 'secondary',
    'padding_block_start' => '0',
    'padding_block_end' => '0',
  ]));
  $cta = $add('sdc.event_horizon.cta', "CTA - {$cl['heading']}", [
    'heading_text' => $cl['heading'],
    'level' => 2,
    'text' => $cl['text'],
    'text_align' => 'center',
    // White text on the secondary band (event_horizon's own text colour
    // there is black). Needs patches/event_horizon-cta-text-color-white.patch.
    'text_color' => 'white',
    'overlay_opacity' => '0%',
  ], $s, 'main_slot');
  $add('sdc.event_horizon.button', 'Button - Get tickets (closing)', $button('Get tickets', 'internal:/#tickets', 'primary-inverted', 'arrow-right'), $cta, 'actions');
  $add('sdc.event_horizon.button', "Button - {$c['second_cta'][0]} (closing)", $button($c['second_cta'][0], $c['second_cta'][1], 'secondary-inverted'), $cta, 'actions');

  return $rows;
};

/**
 * Inputs of the page's current moderator People Card, so the photo
 * reference stays this environment's own.
 */
$current_moderator = function ($page): array {
  foreach ($page->get('components')->getValue() as $row) {
    if ($row['component_id'] === 'sdc.drupal_india_connect.people-card') {
      return json_decode($row['inputs'], TRUE);
    }
  }
  throw new \RuntimeException('No moderator People Card on page ' . $page->id());
};

// ---------------------------------------------------------------------------
// Content. Copy is the pages' own, as approved in the prototypes.
// ---------------------------------------------------------------------------
$pages = [
  'day1' => [
    'alias' => '/ai-summit/building-intelligent-public-infrastructure',
    'content' => fn($page) => [
      'eyebrow' => 'AI Summit · Panel discussion',
      'title' => 'Building intelligent public infrastructure',
      'intro' => 'Open source, Drupal, and AI. How can they work together to make public services easier to find, understand, and use, without losing trust or accountability?',
      'second_cta' => ['Explore the AI Summit', 'internal:/ai-summit'],
      'glance' => [
        ['Format', 'Panel discussion'],
        ['When', '28 January 2027'],
        ['Session time', 'To be announced'],
        ['Where', 'IIT Bombay, Mumbai'],
        ['Moderator', 'Piyuesh Kumar, QED42'],
        ['Panelists', 'Four to five voices, to be announced'],
        ['Part of', 'DrupalAsia Connect India 2027 · AI Summit'],
      ],
      'jump' => [
        ['The conversation', 'conversation'],
        ['Three layers', 'layers'],
        ['A citizen’s journey', 'journey'],
        ['Questions', 'questions'],
        ['The panel', 'panel'],
      ],
      'conversation' => [
        'heading' => 'From digital rails to real access',
        'paragraphs' => [
          'Digital Public Infrastructure creates shared foundations for identity, payments, credentials, data exchange and public services. But the infrastructure itself does not guarantee that someone can discover a service, understand whether they qualify, or complete the next step.',
          'This panel explores the space between public infrastructure and people: trustworthy information, accessible experiences, multilingual services, and the responsible use of AI.',
        ],
        'quote' => 'What becomes possible when public systems, mature open-source platforms, and AI are designed to work together?',
        'quote_caption' => 'The question at the centre of this panel',
      ],
      'joined' => [
        'eyebrow' => 'A possible architecture',
        'heading' => 'Three layers. One public purpose.',
        'intro' => 'Each technology has a different job. The opportunity is to connect them without confusing their responsibilities.',
        'variant' => 'joined-arrow',
        'cards' => [
          ['Foundation', 'Digital public infrastructure', 'Shared systems and APIs for identity, consent, payments, credentials, and service delivery.'],
          ['Experience', 'Open source and Drupal', 'Structured, governed, multilingual information and interoperable citizen-facing experiences.'],
          ['Interaction', 'Artificial intelligence', 'Intent-based discovery, grounded explanations, language and voice interfaces, and carefully authorized actions.'],
        ],
      ],
      'scenario' => [
        'eyebrow' => 'Start with a person, not a platform',
        'heading' => 'What should public access feel like?',
        'note' => 'A simple question can involve several departments, eligibility rules, languages, and steps.',
        'who' => 'A small business owner asks',
        'says' => '“I’ve started a small business. Which registrations, programmes, or benefits should I know about?”',
        'steps' => [
          ['title' => 'Discover authoritative services'],
          ['title' => 'Explain eligibility and requirements'],
          ['title' => 'Help in a preferred language'],
          ['title' => 'Guide the next authorized action'],
        ],
      ],
      'questions' => [
        'eyebrow' => 'On the table',
        'heading' => 'Questions worth asking',
        'intro' => 'Real questions about public value, architecture, access, and responsibility. Not a platform pitch.',
        'items' => [
          ['title' => 'Where can open source contribute more to public digital systems?', 'pill' => 'Public value'],
          ['title' => 'Where can Drupal add value without replacing the underlying DPI?', 'pill' => 'Architecture'],
          ['title' => 'How can people find services by intent, not department?', 'pill' => 'Access'],
          ['title' => 'How do we make multilingual AI explanations accurate and accessible?', 'pill' => 'Access'],
          ['title' => 'What consent, evidence, and safeguards should citizen-facing AI agents require?', 'pill' => 'Responsibility'],
          ['title' => 'What could a useful, responsibly scoped pilot look like?', 'pill' => 'Next steps'],
        ],
      ],
      'panel' => [
        'eyebrow' => 'People in the conversation',
        'heading' => 'A cross-disciplinary panel',
        'intro' => 'We are bringing together perspectives from public infrastructure, open source, AI, and governance. Panelist announcements will follow.',
        'moderator' => $current_moderator($page),
        'seats' => 4,
        'note' => 'We’re shaping a balanced discussion with practitioners from different disciplines. Names and profiles will be added as participation is confirmed.',
      ],
      'closing' => [
        'heading' => 'Better public services, built together.',
        'text' => 'Leave with a clearer view of where open-source platforms, Drupal, and AI can support public infrastructure, and what it takes to keep the experience trustworthy, inclusive, and accountable.',
      ],
    ],
  ],
  'day2' => [
    'alias' => '/conference/whats-next-for-drupal',
    'content' => fn($page) => [
      'eyebrow' => 'Day 2 · Panel discussion',
      'title' => 'What’s next for Drupal?',
      'subtitle' => 'Shaping the next chapter of the open web',
      'intro' => 'A forward-looking conversation on Drupal’s direction as a platform, a product, and an open-source ecosystem.',
      'second_cta' => ['See the full program', 'internal:/program'],
      'glance' => [
        ['Format', 'Panel discussion, 60 minutes'],
        ['When', 'Friday, 29 January 2027'],
        ['Session time', 'To be announced'],
        ['Where', 'IIT Bombay, Mumbai'],
        ['Moderator', 'Saheel Sikilkar, Syngenta'],
        ['Panelists', 'Four perspectives, to be announced'],
        ['Part of', 'DrupalAsia Connect India 2027 · Day 2, Discover'],
      ],
      'jump' => [
        ['The conversation', 'conversation'],
        ['Four perspectives', 'perspectives'],
        ['The next project', 'project'],
        ['Questions', 'questions'],
        ['The panel', 'panel'],
      ],
      'conversation' => [
        'heading' => 'Where is Drupal heading next?',
        'paragraphs' => [
          'Drupal is evolving on several fronts. Core continues to provide an extensible foundation, Drupal CMS is changing how experiences are delivered, emerging site-building approaches are reshaping workflows, and AI is opening up new possibilities.',
          'These changes are connected. But what do they mean for the people building, adopting and contributing to Drupal? And which decisions will matter most over the next few years?',
          'We are bringing four perspectives together for a candid conversation about the choices, opportunities and trade-offs ahead. This is not a sequence of initiative updates or product pitches. The focus is on understanding what Drupal needs to become, what it must preserve, and how the community can help shape its future.',
        ],
        'quote' => 'Expect different viewpoints, practical questions and a clearer picture of the opportunities ahead for Drupal.',
        'quote_caption' => 'What you will leave with',
      ],
      'joined' => [
        'eyebrow' => 'What we will explore',
        'heading' => 'Four perspectives. One ecosystem.',
        'intro' => 'The discussion will connect important parts of Drupal’s evolution, rather than treating each initiative in isolation.',
        'variant' => 'joined-plus',
        'cards' => [
          ['Platform', 'Drupal Core & architecture', 'How should the foundation evolve while retaining the stability and flexibility Drupal is known for?'],
          ['Experience', 'Drupal CMS & site building', 'What needs to change to make powerful digital experiences easier to design, build and maintain?'],
          ['Innovation', 'AI & emerging workflows', 'How might AI-enabled tooling and new interfaces change the way teams work with Drupal?'],
          ['Ecosystem', 'Adoption & sustainability', 'How can Drupal remain relevant, accessible and sustainable for organisations and contributors?'],
        ],
      ],
      'scenario' => [
        'eyebrow' => 'From direction to decisions',
        'heading' => 'Picture the next Drupal project.',
        'who' => 'A team is choosing how to build',
        'says' => 'It wants fast delivery and a clear editing experience, without giving up integration, governance or the ability to extend the platform.',
        'steps' => [
          ['pill' => 'Choose', 'title' => 'The platform', 'body' => 'Why would the team choose Drupal over other approaches?'],
          ['pill' => 'Build', 'title' => 'The experience', 'body' => 'How much can site builders do confidently, and when do developers step in?'],
          ['pill' => 'Evolve', 'title' => 'The ecosystem', 'body' => 'What keeps the solution adaptable, maintainable and sustainable in five years?'],
        ],
        'conclusion' => 'Those are not separate questions. Together, they define what Drupal needs to deliver next.',
      ],
      'questions' => [
        'eyebrow' => 'Questions worth asking',
        'heading' => 'Not just what’s coming. What’s changing?',
        'items' => [
          ['title' => 'Where must Drupal simplify?', 'body' => 'What complexity adds value, and what gets in the way?'],
          ['title' => 'What belongs in Core?', 'body' => 'Which investments strengthen the foundation for everyone?'],
          ['title' => 'How do we measure adoption?', 'body' => 'What would make Drupal the clear choice for more teams?'],
          ['title' => 'Who is Drupal being built for?', 'body' => 'How do builders, editors, product teams and enterprises shape priorities?'],
          ['title' => 'Where does AI help?', 'body' => 'Which workflows improve, and where should we remain cautious?'],
          ['title' => 'What can the community do?', 'body' => 'What should participants contribute to next?'],
        ],
      ],
      'panel' => [
        'eyebrow' => 'On the stage',
        'heading' => 'Meet the conversation',
        'intro' => 'We are bringing together perspectives from across the Drupal ecosystem. We are curating the panelist lineup and will announce it once it is confirmed.',
        'moderator' => $current_moderator($page),
        'seats' => 4,
        'note' => 'We’re bringing together people from across the Drupal ecosystem to explore where the platform is heading, the challenges ahead, and what this means for developers, organizations, and the wider community. Panelists will be announced soon.',
      ],
      'closing' => [
        'heading' => 'The next chapter will be shaped together.',
        'text' => 'Leave with a sharper understanding of Drupal’s strategic choices, the trade-offs that matter, and where your own work or contribution can have an impact. Whether you build Drupal, use it to deliver digital experiences, or contribute to its ecosystem, this discussion is about the choices that shape the future of the open web.',
      ],
    ],
  ],
];

// ---------------------------------------------------------------------------
// Save, with the guards from CLAUDE.md.
// ---------------------------------------------------------------------------
foreach ($pages as $key => $spec) {
  if ($only && $only !== $key) {
    continue;
  }
  $path = $alias_manager->getPathByAlias($spec['alias']);
  if (!preg_match('#^/page/(\d+)$#', $path, $m)) {
    echo "$key: no canvas_page at {$spec['alias']}. Skipped.\n";
    continue;
  }
  $page = $page_storage->load($m[1]);
  $old = $page->get('components')->getValue();
  $rows = $build(($spec['content'])($page));

  $page->set('components', $rows);
  $violations = $page->validate();
  foreach ($violations as $v) {
    echo "$key VIOLATION ", $v->getPropertyPath(), ': ', strip_tags((string) $v->getMessage()), "\n";
  }
  if (count($violations)) {
    echo "$key: not saved.\n";
    continue;
  }
  $page->setNewRevision(TRUE);
  $page->setRevisionLogMessage('Panel page revamp: shared panel-discussion layout.');
  $page->save();

  $page_storage->resetCache([$page->id()]);
  $saved = $page_storage->load($page->id());
  $after = array_column($saved->get('components')->getValue(), 'uuid');
  $lost = array_diff(array_column($rows, 'uuid'), $after);
  echo "$key: canvas_page {$saved->id()} at {$spec['alias']}: ", count($old), ' rows replaced by ', count($after), ' (', count($rows), " built).\n";
  echo $lost ? '  LOST ' . count($lost) . " rows\n" : "  No rows lost.\n";
  echo '  Post-save violations: ', count($saved->validate()), "\n";
  // A stale editor tab would write the old tree back over this.
  \Drupal::keyValue('canvas.auto_save')->delete("canvas_page:{$saved->id()}:en");
}
