# DrupalAsia Connect — Design System

The visual system for **DrupalAsia Connect**, a three-day pan-Asia Drupal event
on **18–20 January 2027**. It is not invented: it is the **Event Horizon**
Drupal theme (an FLDC fork, extended for the Drupal event platform), recoloured
from the event's own logo and extended with the two components the theme was
missing.

## Sources

Everything here was read from, or copied out of, material the client provided.
Nothing is reconstructed from memory.

| Source | What came from it |
| --- | --- |
| `drupal-asia-connect` — a Drupal 11 codebase (mounted local folder) | The entire system. Specifically `web/themes/contrib/event_horizon/`: token set, base element styles, breakpoints, every component's CSS and Twig, the icon set, fonts, and artwork. |
| `web/themes/contrib/event_horizon/README.md`, `AGENTS.md`, `LAYOUTS.md` | Component inventory, Canvas groupings, authoring conventions, deliberate exclusions. |
| `web/themes/custom/drupal_india_connect/` | The existing sub-theme this event ships through (machine name retained). |
| `recipes/summit/config/` | The content model behind the components — Event Details config page, event taxonomy terms, sponsor/speaker/session/news types, Canvas page regions. |
| `uploads/DrupalAsiaConnect-logo.svg` | The event logo, and the two brand colours the whole system derives from. |
| A Google Doc of home-page requirements (link supplied in chat; body not machine-readable to us) | The home page's section list. Details were confirmed with the client directly. |

The upstream theme is a contrib project: <https://www.drupal.org/project/event_horizon>.

## The one rule

**Primary and secondary are derived, not written.** Each ramp is a
hue/saturation/lightness triple in `tokens/colors.css`; the 30/40/50/60/80
steps are computed from it with `calc()`, exactly as the base theme does it.
Rebranding this system for another edition or another city means changing six
numbers. Never hand-write a step, and never paste a hex into a component.

    primary   #0462D1  ->  hsl(213, 96%, 42%)   (logo blue)
    secondary #F66E0E  ->  hsl(25, 93%, 51%)    (logo orange)

The only literal brand hex in the system is the logo's navy, `--dac-navy`
(#0C2152), used for dark bands, and its tint `--dac-sky-tint` (#EAF4FE).

## CONTENT FUNDAMENTALS

**Voice: a community organiser, not a marketing department.** The event is
volunteer-run and says so. Copy is plain, concrete, and slightly
under-sold — it would rather admit a gap than paper over it.

- **Person.** "We" for the organisers, "you" for the reader. Never "the
  organisation" or the passive voice: "We are finalising the host city", not
  "The host city is being finalised."
- **Case.** Sentence case everywhere in prose and buttons. UPPERCASE is a
  *typographic* device, not an editorial one — it belongs to display headings,
  eyebrows and small labels, and the CSS applies it. Never type in caps.
- **Honesty over hype.** Unknowns are stated as unknowns: "Host city
  announcement soon", "Price TBA", "Venue announcement coming soon". A cheerful
  placeholder ("Amazing venue — coming soon!") is off-voice. Never publish a
  number that isn't real; if attendance figures don't exist yet, drop the stats
  band rather than project one.
- **Verb-led CTAs, first person where it reads better.** "Get event updates",
  "Plan to speak", "Keep me posted", "Tell me when the CFP opens", "Notify me".
  Never "Submit", "Learn more", or "Click here".
- **Lengths.** Eyebrow: 3–6 words. Heading: under 8 words, and it makes a claim
  ("One Drupal community, one continent"). Lede: one or two sentences. Card
  body: one or two lines. Anything longer belongs on an inner page.
- **Specificity beats adjectives.** "Mentored sprints where first-time
  contributors get their first issue credit" — not "great opportunities to
  contribute".
- **Dates.** "18–20 January 2027" (en dash, no ordinals, month spelled out).
  Short form "Jan 2027" only in timeline rows. Times are 24-hour with the
  timezone stated when it matters.
- **Inclusion is stated, not implied.** First-time speakers, travel grants,
  visa letters, accessibility and the code of conduct each get plain-language
  copy. This is a regional event across many countries; assume nothing about
  the reader's location, budget or English.
- **No emoji.** Not in UI, not in copy. The system has no emoji anywhere and
  the icon set is deliberately closed.
- **Drupal attribution.** The footer carries "Drupal is a registered trademark
  of Dries Buytaert."

## VISUAL FOUNDATIONS

### Character

Editorial and structural, not decorative. The system's confidence comes from
**enormous uppercase type, hard-edged colour bands and hairline rules** — not
from shadows, gradients or illustration. Nearly every page is a vertical stack
of full-bleed bands; the interest comes from the rhythm of white → tint → navy
and from one very loud hero.

### Typography

- **One typeface: General Sans** (variable, 200–700), served locally from
  `assets/fonts/general-sans/`. No secondary family, no serif, no display
  face.
- Display type is **uppercase, weight 700, leading below 1 (0.86 in the hero,
  1.05 in section headings), tracking +0.01em**. Big type gets *negative*
  tracking at the heading steps (−0.01 to −0.03em) and *positive* tracking when
  small and uppercase (+0.08 to +0.16em). That inversion is the system's
  signature.
- Body copy is 16 → 20px across breakpoints, `line-height` 1.5–1.6, with
  `text-wrap: pretty`. Ledes go one step up.
- Stat figures are 56px/800 with −0.02em tracking; their captions are
  14px/500. This is the only place weight 800 appears outside base `h1`.
- The **eyebrow** — 12px, weight 700, 0.14–0.16em tracking, uppercase, in the
  secondary colour, often with a 2px rule or a 4px left border — opens most
  sections. It is the cheapest way to make something look like this brand.
- Monospace (`--font-mono`, system stack) appears **only** in placeholder
  captions telling you what asset belongs in a slot. Never in product copy.

### Colour

- **Two brand colours, one neutral ramp, one tint family.** Blue carries
  action and structure; orange carries attention and is rationed — one CTA
  band, one accent rule per group of four, the announcement bar. If orange
  appears three times in a viewport, remove two.
- Neutrals are **cool and slightly blue** (the "night" ramp), never pure grey;
  whites are subtly warm-neutral (#FDFDFD page, #FFF cards). Pure black is not
  used for text — `--color--night-5` is.
- **At most two band colours per page**, plus white. In practice: white
  sections, a pale sky tint for alternating bands, navy for dark bands, and
  orange for exactly one closing CTA.
- On navy, body copy goes to a light blue-grey and eyebrows to pale blue —
  never pure white for long text.
- `::selection` is white on `--color--primary-80`.

### Backgrounds and imagery

- Backgrounds are **flat colour**. The one sanctioned gradient is functional:
  the hero's media well carries a secondary→primary wash at 0.5 opacity over
  the video, and the hero title's fill animates between primary and black. No
  decorative gradients, ever, and no purple.
- Photography is used sparingly and **treated**: speaker portraits render
  grayscale at 180% contrast over a colour wedge, colourising on hover; sponsor
  logos sit grayscale until hover. Nothing is dropped in untreated.
- Texture (`assets/textures/`) shows *through* type via `background-clip:
  text` — it is never a page background.
- Missing imagery is shown as a **45° striped placeholder with a monospace
  label** saying what belongs there. Never an illustration, never a hand-drawn
  SVG, never a stock-photo stand-in.

### Shape, borders and elevation

- Radii: 4px badges, 6px buttons/inputs, 8px panels, 12px cards, 24px tiles.
  Bands and hero elements are square-cornered. One deliberate asymmetry
  exists: the sponsor tile's single rounded top-right corner.
- Rules do the work borders usually do: 1px hairline dividers, 2px control
  borders, **3px card accent (top or left)**, 4px heading rule. The accent rule
  is primary, switching to secondary on the last item of a group.
- **One shadow in the whole system** — the framed card's
  `0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)`. There is
  no elevation scale. Depth comes from bands, not lift.

### Motion, hover and press

- Durations: 0.2s for colour/opacity, 0.3s for transform, 0.4s for the
  accordion. Easing is plain `ease` or linear. **No bounce, no spring, no
  overshoot.**
- Hover is a **colour or scale** change, never a lift: buttons darken one ramp
  step (50 → 40); clickable cards scale 1.02; sponsor tiles scale 1.03 and drop
  to 0.9 opacity; links keep their colour and switch the underline to
  secondary; nav links pick up a `sky-97` fill.
- Press state is inherited from the browser; there is no custom active
  treatment. Focus is a **2px `currentColor` outline at 2–3px offset** with a
  2px radius — visible, never removed.
- Scroll-driven animation appears twice, both via `animation-timeline: view()`:
  the hero title's fill and the speaker portrait wedge. Group entrance fades
  (0.5s, 1rem travel) exist but should be used at most once per section. Every
  one of these is disabled under `prefers-reduced-motion: reduce`.

### Layout

- 1400px max width (`--layout--max-width`), centred, with 16–32px inline
  padding. A 6/12/14-column grid at 600/1300px.
- Breakpoints are **container style queries**, not media queries: `--sm` 500,
  `--md` 700, `--lg` 1000, `--wide-nav` 1100, `--grid-md` 600, `--grid-max`
  1300. Navigation switches at 1100px, not 1000.
- One 16px-anchored spacing scale (`--sp*`); grid gap 1.5rem rising to 2rem on
  the widest screens. Section bands carry 5–5.5rem of block padding.
- Coloured bands bleed to the viewport edge (`--full-width`, or
  `border-image-outset: 0 50vw` in the older view CSS) while their content
  stays on the grid.
- Fixed positioning is used only for the sticky header and the sticky schedule
  time-slot column. No floating action buttons, no sticky footers.

### Transparency and blur

Almost none. `rgba(255,255,255,0.06–0.07)` panels on navy bands, a 0.5-opacity
gradient over hero video, a 0.8-opacity scrim on the hero play button, and
white overlay scrims (0–75%) behind text on photographic heroes. Blur appears
once — the sticky header's `backdrop-filter: blur(10px)`. Frosted-glass
surfaces are not part of this system.

## ICONOGRAPHY

- **Phosphor Icons (regular)**, inlined as paths in a **closed 18-glyph set**:
  arrow-right, arrow-left, caret-right, caret-left, download, user-plus,
  rocket, play-circle, pause-circle, star, check, x, calendar, search, mail,
  phone, map-pin, external-link. All 256×256 viewBox, `fill="currentColor"`,
  `aria-hidden`. `components/base/Icon.jsx` carries the same paths as the
  theme's `icon.twig`, copied verbatim.
- Sizes are named, not free: 20 / 24 / 32 / 48 / 64px.
- There is **no icon font and no sprite sheet**. A handful of standalone SVGs in
  `assets/icons/` (copied from the theme) are used as CSS `mask-image` marks —
  the breadcrumb home glyph, the hero arrow chip, the calendar button, the
  chevron, search, RSS, PDF, user.
- Brand-flavoured marks exist for the event platform: `drupal-evergreen.svg`,
  `drupal-rocket.svg`, `palm-tree.svg` (used in the stats band). These come
  from the theme.
- **No emoji. No unicode glyphs as icons. No hand-drawn SVG.** If a design
  needs a glyph outside the 18, add it to the theme's `icon.twig` map first so
  Drupal and this system stay in sync.

## Components

Grouped as the Canvas UI groups them. This is the theme's **actual** inventory —
no invented primitives.

**Base** (`components/base/`) — Button, Badge, Icon, Heading, Text,
Blockquote, Image, Video
**Layout** (`components/layout/`) — Section, Group, Navbar, Footer, Accordion,
AccordionContainer
**Card** (`components/cards/`) — Card, CardIcon, CardLogo, CardTestimonial,
StatCard, SponsorTier
**Hero** (`components/hero/`) — HeroBillboard, HeroSideBySide, Cta
**Event platform** (`components/event/`) — SiteHero25, SiteHeader,
SiteHeroDetails, SiteStats, FeaturedSpeaker, SponsorTeaser, Countdown,
AnnouncementBar

### Intentional additions

Three, all shipped in the sub-theme as new Drupal SDCs and mirrored here:

- **Countdown** — the live countdown to day one. The base theme has no
  countdown, and a save-the-date homepage needs one.
- **AnnouncementBar** — one line of site-wide news above the header. The theme
  has no equivalent region component.
- **SponsorTier** (`sponsor-tier`) — a sponsorship package: tier name, price,
  a benefits checklist, and an optional CTA. This is distinct from the
  excluded `card-pricing` below — it's informational (what a sponsor gets),
  not a ticket-sale flow. `tier_accent` colours the top border/label from
  existing tokens (secondary, primary-50, primary-60, neutral); spend
  secondary on at most one tier per page.

### Deliberately absent

- **card-pricing** — excluded upstream on purpose: ticketing belongs to
  commerce/registration, not to a Canvas component. Ticket tiers are built from
  `Card` until real pricing exists. Sponsorship tiers are a different thing —
  see `SponsorTier` above.
- **site-hero** (the pre-2025 hero) — superseded by `SiteHero25`; building
  both would invite the wrong one being used.
- **details** — a Drupal render-array wrapper for file/attachment lists with no
  meaningful props outside Drupal.
- **hero-blog**, **anchor** — marked obsolete or trivial upstream.

## Index

| Path | What it is |
| --- | --- |
| `styles.css` | The CSS entry point — `@import` lines only. Link this one file. |
| `tokens/` | `typography.css` (@font-face + type scale), `colors.css` (derived ramps + semantic aliases), `spacing.css`, `layout.css` (radii, motion, the one shadow). |
| `css/base/` | Base element styles, breakpoints, container/grid, utilities — copied verbatim from the theme. |
| `css/components/` | Every component's real CSS, copied verbatim, plus `countdown.css` and `announcement-bar.css`. `css/components/images/` holds the masks those files reference. |
| `css/layout/` | The featured-speakers and session-schedule view layouts. |
| `components/` | React recreations — `.jsx`, `.d.ts` props contract, `.prompt.md` usage note, and one `@dsCard` HTML per group. |
| `guidelines/` | 21 foundation specimen cards (Colors, Type, Spacing, Brand). |
| `ui_kits/event_site/` | Click-through recreation of the event site: home, speakers, schedule, sponsors. Start here to see the system assembled. |
| `assets/` | `logo.svg`, `fonts/general-sans/`, `icons/`, `textures/`. |
| `reference/` | The approved home-page design this system was extracted from, with the fonts and script it needs. Open `reference/DrupalAsia Connect Home.dc.html` in a browser. |

The Claude Code skill that fronts this system is at
`.claude/skills/drupalasia-connect-design/SKILL.md`, kept there because that
is where Claude Code discovers skills; it points back at this directory.

Also in this project, outside the design system proper:

| Path | What it is |
| --- | --- |
| `web/themes/custom/drupal_india_connect/` | The deployable Drupal sub-theme, and the source of truth for what actually ships. It has moved past this handoff in places — see the note below. |
| `CLAUDE.md` | The traps in this codebase: editing Canvas content safely, anchors keyed to heading text, config export. |

### Where the build has diverged

This system was written from the handoff. The implementation has since
changed some of it deliberately, and the live theme wins:

- The hero title is set in the logo's sentence case, not the system's
  all-caps display type. Section headings keep the caps.
- The homepage hero is a custom `hero` SDC, not the base theme's
  `site-hero-25`.
- Type in the hero sizes against its grid column with `cqw` rather than the
  viewport, because it shares a row with the media well.

## Caveats

- **Fonts are the real files**, copied from the theme — no Google Fonts
  substitution was needed. General Sans is licensed from Fontshare; confirm the
  licence covers your deployment.
- **The logo SVG has a white canvas baked in** (4096×2736 with a #FDFDFD
  background rect). On dark bands it needs a white plate. A trimmed-viewBox,
  transparent version would be worth producing.
- **No event photography exists yet.** Speaker portraits, venue shots and
  sponsor logos are placeholders or theme artwork throughout.
- **No teaser video yet.** `SiteHero25` falls back to its poster.
