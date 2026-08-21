# Working in this repo

Drupal 11 event site on DDEV. See `README.md` for the stack and layout.
This file is the things that have already cost time here.

## Commands

```bash
ddev start && ddev composer install   # bring the environment up
ddev drush cr                          # cache rebuild, after config/theme changes
ddev drush cim / cex                   # import/export config (see Config below, never bare cex)
ddev launch                            # open the local site
```

There is no automated test suite and no theme build step (no
`package.json`, no PHPUnit tests outside core/contrib) — verify changes by
loading the site (see **Verifying** below), not by looking for a `npm test`
or `phpunit` to run.

## Ground rules

- Run everything through DDEV: `ddev drush …`, `ddev composer …`.
- **`ddev export-db` before any script that writes entities.** Several
  changes in this project have been recovered from those dumps.
- Do not push unless asked. Merging to `main` deploys nothing by itself —
  see **Deployment is tag-based** below for what actually ships.
- `main` has GitHub branch protection: force-push and branch deletion are
  rejected at the remote. Undo a bad push with a revert commit, not
  `push --force` (which would just fail).

## Deployment is tag-based

`.github/workflows/deploy.yml` only fires on a semver tag push (`1.2.3` or
`v1.2.3`, no pre-release suffix) or a manual run with an existing tag as
input — pushing/merging to `main` ships nothing on its own. Cutting a
release is `git tag 1.2.3 && git push origin 1.2.3`; the same manual-run
path with an older tag is how you roll back (code only — the database does
not roll back with it, see the workflow's own comments).

The deploy script stashes any server-only tracked-file edits (the staging
Basic Auth block in `web/.htaccess`) before pulling and restores them
straight after — **this has already gone wrong once and left the site
public**, when something failed between the stash and the pop. If a deploy
fails partway, check `git stash list` on the server and `web/.htaccess`
before assuming the site is still behind auth.

## Design work

The brand system is at `design-system/`, fronted by the
`drupalasia-connect-design` skill. Read `design-system/readme.md` before
designing anything, and never paste a brand hex — the primary and secondary
ramps are derived from hue/saturation/lightness triples in
`design-system/tokens/colors.css`.

`design-system/reference/` holds the approved homepage prototype. Diff
against it before rebuilding a section from description; working
section-by-section from screenshots has repeatedly produced invented copy
here, when the prototype already had the real words.

**The built theme is the source of truth where the two disagree.** The
divergences are listed at the end of `design-system/readme.md` — the hero's
sentence case and column-relative sizing are deliberate, not drift.

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
php scripts/check-canvas-folder-duplicates.php /var/www/html/cex-full
```

Known noisy config: `canvas.component.*` entities carry an `active_version`
hash that churns, and `canvas.page_region.drupal_india_connect.header` must
stay `status: false` — enabling it 500s `ApiLayoutController::get()` and
breaks the live site header.

**Canvas folders duplicate silently — always run the check script above
before copying an export into `config/sync`.** `Component::postSave()`
auto-creates a Folder *by name* the first time a component is saved into a
category that doesn't have one yet, but Drupal's config system tracks
Folders by UUID — so if discovery ever runs before a fresh environment's
first `drush cim` (e.g. someone browses admin pages, which triggers a
cache rebuild and component discovery, before importing config), it
creates a second same-named folder that import can never merge with the
"real" one. This had already happened to 20 of 21 folders (43 entities for
21 names) before anyone noticed, because the only symptom is `drush cron`
throwing `RuntimeException: It is impossible for an item to exist in
multiple Folders` — nothing points back to config/sync. Run
`php scripts/check-canvas-folder-duplicates.php config/sync` any time to
check what's already committed.

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

## Syncing the database from production

Use the `db-sync-from-live` skill (`.claude/skills/db-sync-from-live/`)
rather than running ssh/scp/drush by hand — it backs up local first, dumps
remote into a throwaway temp dir, cleans that up, then imports and checks
`config:status`. It also already knows the one thing that isn't obvious
from the server itself:

- **Production's default `php` on PATH is 8.3; this Drupal's Composer
  dependencies need 8.4.** Running `vendor/bin/drush` there with the
  default PHP prints a Composer platform-requirement warning and exits
  with no further output — it looks like drush hung or failed silently,
  not like a version mismatch. The fix is a CloudLinux/cPanel-style
  alternate PHP build (`/opt/alt/php84/usr/bin/php` on this host): put it
  first on `PATH` before invoking `vendor/bin/drush`, or set
  `REMOTE_PHP_BIN` in `.claude/db-sync.local.env`.
- SSH host/port/user and the remote app path live only in
  `.claude/db-sync.local.env` (gitignored, one per machine) — never in this
  file or in the skill's own scripts, since both are committed.

## Environment notes

- **Environment variables do not cross into the DDEV container.**
  `FLAG=1 ddev drush php:script x.php` will not see `FLAG` — a dry-run
  guard written that way silently runs for real.
- **`drush php:script` only sees files inside the project**, not
  `/tmp`. Write scripts to the project root and delete them after.
