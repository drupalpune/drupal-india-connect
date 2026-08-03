# Summit (aka Event Platform Starter)

This site template recipe ("Summit") is a quick way to get started running a
conference, camp, or similar multi-track event on Drupal. Applying it
installs a complete, ready-to-launch event website: a call-for-proposals
(CFP) pipeline with session moderation and scoring, a schedule builder with
rooms and time slots, tiered sponsor management, a job board, speaker
showcases, and an editorial workflow that carries a conference edition
through its entire lifecycle — announced, open for proposals, scheduled,
underway, and complete.

It builds on Event Platform's companion theme,
[Event Horizon](https://www.drupal.org/project/event_horizon), and its
companion module, [Event Platform Helper](https://www.drupal.org/project/event_platform_helper),
and ships with an example event and default content so you can see how
everything fits together immediately after install.

For a full description of the recipe, visit the
[project page](https://www.drupal.org/project/event_platform_starter).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/event_platform_starter).


## Table of contents

- Features
- Requirements
- Application
- Documentation
- Maintainers


## Features

- **Call for proposals & session moderation** — speakers submit sessions,
  moderators score submissions against configurable criteria, then
  accept, waitlist, or reject them from a dedicated moderation dashboard.
- **Scheduling** — sessions and BOFs are placed into rooms and time slots,
  with a public schedule grid, per-slot "concurrent sessions" blocks, and
  attendee-facing personal schedule bookmarking.
- **Tiered sponsorship management** — sponsors can hold different tiers
  across different events without editing their own profile, with a
  ready-made sponsor logo wall and a sponsor-linked job board.
- **Speaker showcase** — a homepage speaker/keynote promotion type,
  independent of the CFP pipeline.
- **Multi-event support** — every piece of content is scoped to an event
  taxonomy term, so the same site can run more than one edition of your
  conference over time.
- **Editorial workflows** — separate content moderation workflows for
  events, sessions, and general pages, so each can move through the states
  that make sense for it.
- **Component-based page building** — the homepage and other landing pages
  are built visually using Drupal Canvas and the Event Horizon theme's
  component library.

See [CONTENT-STRUCTURE.md](CONTENT-STRUCTURE.md) for a full breakdown of the
content types, taxonomies, views, and workflows this recipe provides, and
how they relate to each other.


## Requirements

This recipe requires a large number of dependencies. Pulling them in
automatically by using composer is strongly recommended.


## Application

Begin by installing Drupal CMS. If you want to do this locally, follow the
steps in the [DDEV documentation](https://docs.ddev.com/en/stable/users/quickstart/#drupal-drupal-cms).

If bringing this recipe in via composer, use:
```ddev composer require drupal/event_platform_starter:^2.0```

You should then be able to select the Event Platform site template during the
CMS installer. After that your site will be ready to start customizing!


## Documentation

- [CONTENT-STRUCTURE.md](CONTENT-STRUCTURE.md) — content types, taxonomies,
  custom entities, config pages, webforms, editorial workflows, views, and
  how they all relate to each other.
- [AGENTS.md](AGENTS.md) — technical documentation on how the recipe works
  under the hood (Canvas page regions, automation, "current event"
  resolution), how it relates to its companion projects Event Horizon and
  Event Platform Helper, and guidance for triaging issues that may need a
  fix in more than one of those projects (or in another dependency).


## Maintainers
[//]: # cSpell:disable
[//]: # Do not add maintainers to cspell-project-words file

- Martin Anderson-Clutz - [mandclu](https://www.drupal.org/u/mandclu)
- Bernardo Martinez - [bernardm28](https://www.drupal.org/u/bernardm28)
