# AGENTS.md

Technical documentation for anyone — human or AI agent — working on this
recipe, or triaging an issue that touches it.

For the content model (content types, taxonomies, views, workflows), see
[CONTENT-STRUCTURE.md](CONTENT-STRUCTURE.md). This document covers how the
recipe is built and how it fits together with its two companion projects.


## What this recipe is

`event_platform_starter` (recipe name: "Summit") is a Drupal **site
template** recipe: a declarative, no-code bundle of `config/` (and some
`content/`) that, applied once during site install, produces a
ready-to-launch conference/event website. It is not a module — it contains
no PHP of its own. Site templates don't support update paths; once applied,
a site is expected to diverge from the recipe over time, and the recipe
itself is versioned independently (see `composer.json`, `^2.0`).

Two companion projects ship the actual code this recipe's config depends on:

- **[Event Horizon](https://www.drupal.org/project/event_horizon)** — the
  default theme (`drupal/event_horizon`), which this recipe sets as
  `system.theme.default`.
- **[Event Platform Helper](https://www.drupal.org/project/event_platform_helper)**
  (`drupal/event_platform_helper`) — a module providing shared services,
  blocks, and hooks that both the theme and the recipe's own config rely on.

Beyond those two, the recipe pulls in a large dependency graph (see
`composer.json`/`recipe.yml`) — most importantly **Drupal Canvas**
(`drupal/canvas`, component-based page building and page-region layout),
**Storage** (`drupal/storage`, the generic lightweight content entity used
for sponsorships/CTAs/stats), **Smart Date**, **ECA**, **Content
Moderation**, **Scheduler**, and **Webform**.


## How page rendering actually works

The site's default theme (Event Horizon) is a standalone theme (not a base
theme), with classic Drupal regions declared in its `.info.yml`
(`header`, `highlighted`, `content_above`, `content`, `content_below`,
`sidebar`, `footer`, etc.). However, **this recipe ships
`canvas.page_region.event_horizon.*` config for those regions**, and the
moment even one `PageRegion` entity exists for a theme, Drupal Canvas swaps
the entire site's page display variant to `CanvasPageVariant` — every
region with a matching `PageRegion` entity is rendered by Canvas's
component-tree system instead of classic Block Layout, site-wide, not just
for the migrated regions. Any theme region *without* a `PageRegion` entity
renders empty.

Practical implications:

- Classic Block Layout config (`block.block.*.yml`) mostly still exists in
  this recipe's `config/`, but is **inert** for any themed region that has a
  Canvas `PageRegion` — it's kept around because Canvas's own region-build
  process (`PageRegion::createFromBlockLayout()`) reads from it, and because
  Event Horizon's preprocessing re-derives block *visibility* from it (next
  point).
- Canvas's `PageRegion` component trees have no concept of classic block
  **visibility conditions** (an acknowledged gap in Canvas's own roadmap).
  Event Horizon's `EventHorizonHooks::preprocessPage()`
  (`src/Hook/EventHorizonHooks.php`) reimplements this manually: it hardcodes
  a map of Canvas component-instance UUID → classic block-config ID per
  region, loads each classic `Block` entity, checks `->access('view')`, and
  removes the corresponding component-tree node if access is denied.
  **This is inherently fragile**: it's keyed to specific component-instance
  UUIDs minted when the region was last built. Any full region rebuild
  (including one triggered by `PageRegion::createFromBlockLayout()` — see
  the next section) can mint new UUIDs and silently break this map, with no
  error — visibility conditions just stop applying.
- The site's homepage (and other component-built landing pages) is a
  **separate entity type**, `canvas_page`, not a `node`. See
  CONTENT-STRUCTURE.md's "Pages vs. Canvas pages" section.

Two prior real-world bugs are documented in detail under `docs/` and are
worth reading before touching anything Canvas/theme-region related:

- `docs/canvas-issue-context-mapping.md` — saving the theme settings form
  (e.g. just uploading a logo) fatals whenever any placed block has a
  (possibly empty) `context_mapping` key in its settings, because
  `PageRegion::createFromBlockLayout()` doesn't strip that key before
  validating. Root-caused in Canvas core, worked around in this recipe by
  stripping the stale `context_mapping` key from the affected
  `block.block.event_horizon_views_block__*` config entities.
- `docs/manual-validation-context-mapping-blocks.md` — the manual
  verification steps used to confirm that fix didn't change any block's
  actual filtering behavior (the affected views all derive the same
  argument independently from the current route via their own
  `default_argument_type` plugin).
- `docs/event-sponsors-add-content-bundle-typeerror.md` — a good example of
  how a config-level bug in this recipe (`views.view.event_sponsors.yml`
  pointed an "Add content by bundle" header at the wrong entity type) only
  surfaced as a Canvas rendering fault ("Component failed to render") in
  the *theme's* sidebar region, and only for anonymous visitors. See "Cross-
  project issue triage" below — this is the shape of bug to expect.


## Where automation logic lives

The recipe installs a large set of `eca_*` submodules and ships its own
`eca.eca.*.yml` model config entities (declarative Event–Condition–Action
graphs). This is where automation like advancing an `event` term through
the Event Planning workflow as deadlines pass actually happens — driven by
lookup queries in the `terms_update` view (see CONTENT-STRUCTURE.md).
Neither Event Horizon nor Event Platform Helper implements any ECA plugin,
condition, or action in PHP; all ECA logic is recipe-owned config, not
code. When an automation isn't firing as expected, look at the relevant
`eca.eca.*.yml` model and the `terms_update` view's filters first, not the
module code.


## "Current event" resolution

Nearly every event-scoped block/view needs to know which `event` term is
"current." That logic is centralized in Event Platform Helper's
`EventHelper::determineEvent()`: it first tries to parse an event from the
current path (`/events/<name>`), and falls back to the `event_details`
Config Page's `field_current` reference. Both Event Horizon's preprocessing
and the recipe's own views/blocks rely on this same resolution — if a block
or view is showing the wrong event's content, check whether it's actually
calling `EventHelper::determineEvent()` or independently (and possibly
incorrectly) deriving "current event" some other way.

A related, non-obvious integration point: because Canvas renders Single
Directory Components via `#type => 'component'` → `inline_template` →
`renderInline()`, it **bypasses Drupal's normal preprocess pipeline**
entirely. Any component that needs "current event" context (or other
data normally supplied by a `hook_preprocess`) when rendered through
Canvas must instead call Event Platform Helper's
`EventPlatformTwigExtension` Twig functions directly from the component's
Twig template — see `getHeroContext()`, used by `site-hero-25.twig`, as the
reference pattern.


## Storage entities: a shared, undefined-locally schema

The `sponsorship`, `cta`, and `stat` bundles used throughout this recipe
(see CONTENT-STRUCTURE.md) belong to the `storage` entity type, which is
defined by the **`drupal/storage`** contrib module — not by this recipe,
Event Horizon, or Event Platform Helper. All three of those projects
*consume* that schema by hardcoded bundle/field name (e.g. both
`EventHorizonHooks` and Event Platform Helper's block plugins query
`storage` entities of bundle `sponsorship` directly). A bundle rename or
field change would require coordinated fixes across the recipe's config
*and* both companion projects' PHP — there's no single owner to patch.


## Cross-project issue triage

Because of the points above, **an issue reported against this recipe may
require a fix in Event Horizon, in Event Platform Helper, in a third-party
dependency (like `drupal/storage`, `drupal/canvas`, or
`add_content_by_bundle`), or in more than one of those at once.** Don't
assume the symptom's location is the defect's location. Concretely, when
triaging:

1. **A rendering error/blank region on a specific page** → check whether a
   Canvas `PageRegion` component failed to render (look for a
   `RenderSafeComponentContainer` log entry naming the failing component and
   region) before assuming the theme is at fault. Trace from there: is the
   failing component backed by a view or block whose config, in this
   recipe, references the wrong bundle/entity type (as in the
   `event_sponsors` bug)? Is it a `storage`-entity lookup that assumes a
   bundle/field that no longer matches the recipe's config?
2. **A fatal on saving theme settings, or any full Canvas region rebuild
   behaving unexpectedly** → suspect `PageRegion::createFromBlockLayout()`
   and the classic `block.block.*` config it reads from (recipe-owned), not
   just Canvas's code. Re-check whether Event Horizon's hardcoded
   UUID-to-block visibility map (`EventHorizonHooks::preprocessPage()`) is
   still valid after any such rebuild.
3. **"Current event" context is wrong somewhere** → check
   `EventHelper::determineEvent()` usage first (Event Platform Helper), then
   whether the affected component is Canvas-rendered and therefore needs
   the Twig-extension pattern above instead of a normal preprocess hook.
4. **Something about session/sponsor/room/time-slot data looks
   inconsistent** → re-check the field-level relationships in
   CONTENT-STRUCTURE.md; a surprising number of "the wrong sponsor tier/
   room/slot is showing" reports trace back to a Views relationship or
   selection-handler misconfiguration in this recipe's `config/`, not to
   application code.

When a fix does need to land in a companion project, remember Event
Horizon has its own `AGENTS.md` (component-authoring conventions, SDC/Canvas
gotchas, BEM/CSS-variable conventions) — read it before adding or modifying
theme components. Event Platform Helper has no equivalent document yet;
its `src/` layout is conventional (Hook classes under `src/Hook/`, block
plugins under `src/Plugin/Block/`, cache contexts under
`src/Cache/Context/`, the central `EventHelper` service, and a Twig
extension) and is small enough to read directly.

The `docs/` directory in this repository is a running log of issues
investigated this way — read it before starting new investigations, and
add to it when you resolve a non-obvious cross-project issue, so the
reasoning isn't lost.


## Making changes to this recipe

This recipe's `config/` and `content/` are meant to be edited by applying
changes to a real running Drupal site and exporting, not by hand-authoring
YAML — several config types here (notably Canvas's `PageRegion` and
`ContentTemplate` entities) contain internal version hashes and
key-encoding that only Drupal itself can produce correctly. The general
loop:

```shell
# make the change through the UI, Drush, or a drush php-eval/php-script
# against Drupal's own entity APIs
ddev drush config:export --destination=../recipes/event_platform_starter/config
```

Verify rendered output (curl the affected pages, or check logs for Canvas
component render failures) before exporting, and again after, to confirm
the exported config reproduces the same behavior.

See `GET-STARTED.md` for the full site-template authoring/export workflow,
publishing steps, and the hard constraints all site templates must follow
(no dependency patches, no pinned versions, no install-profile dependency,
etc.).
