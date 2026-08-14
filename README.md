# DrupalAsia Connect

Website for **DrupalAsia Connect 2027** — the first pan-Asia Drupal event, at
IIT Bombay, Powai, Mumbai, 21–23 January 2027.

Production: <https://drupalasia.org>

## Stack

| | |
|---|---|
| Drupal | 11.3 (PHP 8.4, MariaDB 11.8) |
| Base theme | [`event_horizon`](https://www.drupal.org/project/event_horizon) 2.x |
| Site theme | `web/themes/custom/drupal_india_connect` |
| Page building | [Drupal Canvas](https://www.drupal.org/project/canvas) 1.5 |
| Event data | `event_platform_helper` (event term, dates, location) |
| Also | `webform`, `smart_date`, `klaro` (cookie consent) |

There are no custom modules. All custom code is the sub-theme.

## Local development

Requires [DDEV](https://ddev.readthedocs.io/).

```bash
ddev start && ddev composer install
```

The site needs a database — it is not built from config alone (see
[Content lives in the database](#content-lives-in-the-database)). Import a
dump from production or from a teammate:

```bash
ddev import-db --file=path/to/dump.sql.gz
```

Then:

```bash
ddev drush cr && ddev launch
```

Local site: <https://drupal-india-connect.ddev.site>

### settings.local.php

`web/sites/default/settings.local.php` is gitignored and holds local-only
overrides. It is not required, but CSS/JS aggregation is worth turning off
while doing front-end work — aggregated asset URLs carry long query strings
that some content blockers drop, which makes stylesheet changes look like
they are not applying:

```php
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
```

On the production server the same file holds the database credentials,
`hash_salt` and `trusted_host_patterns`. Do not commit it.

## Layout

```
config/sync/                     exported configuration
recipes/summit/                  site install recipe
web/themes/custom/
  drupal_india_connect/
    components/                  SDC components (see below)
    css/base.css                 design tokens, colour ramps
    css/global.css               overrides on top of event_horizon
    js/section-anchors.js        homepage anchor ids + scroll offset
    drupal_india_connect.theme   announcement bar, footer trademark
design_handoff_drupalasia_home/  the approved design and its prototype
```

### Components

Standard [SDC](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components):
a `.component.yml` schema beside a `.twig` template, with any `.css`/`.js`
of the same name picked up automatically.

Components written for this site: `hero`, `eyebrow`, `fact`,
`media-placeholder`, `announcement-bar`. The rest came with the theme
bundle. Anything the base theme already provides should be used as-is —
`section`, `group`, `heading`, `text`, `button`, `badge` and `accordion`
all come from `event_horizon`.

## Content lives in the database

The homepage is a Canvas page (`canvas_page` entity **1**, "Home", 114
components). Its content is **not** in `config/sync` and is **not** in git.

That means:

- A fresh checkout plus `drush cim` gives you a working site with an empty
  homepage. You need a database.
- Copy changes, menu links, taxonomy terms and node content all move
  between environments by database sync, not by deploy.
- Only theme files and configuration are deployed by pushing to `main`.

## Deployment

Push to `main` → `.github/workflows/deploy.yml` → SSH to the server, which
runs `git merge --ff-only`, `composer install --no-dev`, then
`drush updb`, `drush cim`, `drush cr`.

Because the deploy runs `drush cim`, **anything you changed through the
admin UI must be exported to `config/sync` or the deploy will revert it.**
Check before pushing:

```bash
ddev drush config:status
```

See `CLAUDE.md` for how to export safely — a bare `drush cex` in this
project has staged unrelated files and resurrected deleted configuration
that broke production deploys.

## Before going live

Still outstanding:

- **Code of conduct** and **Privacy policy** pages. The footer links to
  both are disabled because the pages do not exist; the privacy node
  (`/privacy-policy`) is an unedited Drupal stub.
- **News** — the section and its nav links were removed because there are
  no published articles.
- **Sponsors** — none yet. The band is an invitation to sponsor; the
  sponsor view stays in place for when there are real ones.
- **Mobile** — the four-up "Why attend" grid does not collapse on phones.
