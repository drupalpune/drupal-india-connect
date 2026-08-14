# Working in this repo

Drupal 11 event site on DDEV. See `README.md` for the stack and layout.
This file is the things that have already cost time here.

## Ground rules

- Run everything through DDEV: `ddev drush …`, `ddev composer …`.
- **`ddev export-db` before any script that writes entities.** Several
  changes in this project have been recovered from those dumps.
- Do not push unless asked. Deploys to production run on push to `main`.

## The homepage is data, not code

The homepage is `canvas_page` entity **1**, a flat list of ~114 rows in the
`components` field, each `{uuid, parent_uuid, slot, component_id,
component_version, inputs (JSON), label}`, forming a tree via `parent_uuid`.

Canvas's drag-and-drop editor is unreliable here, so content is edited by
script against that field. When you do:

- **Guard every save.** Compare `array_column($rows, 'uuid')` before and
  after and report anything lost. A save that silently drops rows takes
  whole sections with it.
- **Never address rows by array index across saves.** Array order is not
  render order and does not survive edits. Match on `component_id` or on
  content, then resolve descendants through `parent_uuid`.
- **Indented debug output lies.** Printing rows in array order with
  computed depth makes unrelated rows look like children. Resolve
  parentage explicitly before deleting a subtree.
- `parent_uuid` and `slot` must be `null` for top-level rows, never `''`.
  An empty string causes `TypeError` in `ComponentTreeItemList::getHydratedValue()`.
- `label` must be non-blank on every row — Canvas validates it `NotBlank`
  and shows it as the layer name. Blank labels produce one validation
  error per row and the page will not save from the editor.
- Validate after saving: `$entity->validate()` should return 0 violations.
- Rich-text props are stored either as a plain string or as
  `{value, format}` depending on the row. Handle both shapes.
- `block.webform_block` requires a `lazy` key in `inputs` or `preSave()`
  throws `LogicException`. `views_block` does not have that key at all.

### Canvas autosave can overwrite your work

Open editor sessions keep a draft in the `canvas.auto_save` key-value
collection (`canvas_page:1:en`). A stale tab will happily write its old
tree back over the page — this has already cost 41 components once. If the
page loses content and no script wrote it, suspect an open editor tab.

```bash
ddev drush php:eval '\Drupal::keyValue("canvas.auto_save")->delete("canvas_page:1:en");'
```

## Homepage anchors are assigned by JavaScript

Canvas strips `id` attributes from rich-text props, and `event_horizon`'s
`section` renders no attributes object, so the nav anchors (`#about`,
`#tickets`, …) are applied in `js/section-anchors.js`, **matched on each
band's heading text**.

**Change a section heading and you must update `BY_HEADING` in that file**,
or the header nav and footer links to that band go dead.

The same file measures the sticky header and publishes
`--dac-header-height`, which `global.css` uses for `scroll-margin-top` on
`.dac-anchor-target`. Without it, anchors land with the heading behind the
header.

## Config

`config/sync` had drifted 35 files from the database at one point, so a
deploy would have reverted most of the work. Check with
`ddev drush config:status` before pushing.

**Do not run a bare `drush cex`.** It has previously staged ~50 unrelated
files and resurrected `event_platform_scheduler.settings`, an orphan whose
presence breaks the production `drush cim`. Export to a scratch directory
and diff first:

```bash
ddev drush config:export --destination=/var/www/html/cex-full -y
# diff cex-full/*.yml against config/sync/, copy only what you intend
```

Known noisy config: `canvas.component.*` entities carry an `active_version`
hash that churns, and `canvas.page_region.drupal_india_connect.header` must
stay `status: false` — enabling it 500s `ApiLayoutController::get()` and
breaks the live site header.

## Front-end gotchas

- **New component/asset directories need permissions.** Files created by
  scripts land as `600`/`700` and the webserver cannot read them:
  `chmod 755` directories, `644` files. This has silently broken CSS and JS
  more than once.
- **Browser page cache, not Drupal, is usually why CSS "isn't applying".**
  Verify with a unique query string (`/?v=<random>`), not a plain reload.
- `html { overflow-x: clip }` in `base.css` — **not `hidden`**. `hidden`
  makes `<html>` a scroll container and silently kills `position: sticky`.
- `scrollIntoView({behavior: 'auto'})` defers to the CSS `scroll-behavior`,
  which is `smooth` here. Use `'instant'` for a jump.
- Avoid `requestAnimationFrame` for anything that must run on load —
  frames are paused in background tabs, so it never fires.
- Colours come from the derived `--color--primary-*` / `--color--secondary-*`
  ramps, never raw hexes. Section backgrounds are
  `eh-section--bg-{primary|secondary|accent|muted|black}`; dark bands key
  off `--neutral-dark`.

## Verifying

The admin toolbar and the Canvas edit bar pollute measurements, and admins
see unpublished content. To check what a visitor actually gets:

```bash
curl -sk "https://drupal-india-connect.ddev.site/?v=$RANDOM"
```

For rendered/layout checks, fetch the page with `credentials: 'omit'` and
write it into an iframe — an iframe also gives a real narrow viewport for
mobile work, which a desktop browser window cannot go below ~500px to
reach.

## Environment notes

- **Environment variables do not cross into the DDEV container.**
  `FLAG=1 ddev drush php:script x.php` will not see `FLAG` — a dry-run
  guard written that way silently runs for real.
- **`drush php:script` only sees files inside the project**, not
  `/tmp`. Write scripts to the project root and delete them after.
