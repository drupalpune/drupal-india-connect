# Content Structure

This document describes the content architecture that Event Platform Starter
installs: the content types, taxonomies, custom entities, config pages,
webforms, editorial workflows, and views that make up the "Summit" event
platform, and how they relate to one another.

If you're customizing a site built from this recipe, use this as a map before
you start adding fields or views of your own — most new features hang off of
one of the relationships described below rather than needing an entirely new
content type.


## The hub: the `event` taxonomy

Almost everything in this recipe is scoped to an **event** — one taxonomy
term in the `event` vocabulary represents one edition of your conference
(e.g. "DrupalCamp 2027"). An `event` term carries:

- `field_dates` — the event's start/end date range.
- `field_location_short` — a short location label (e.g. "Orlando, FL", or
  "Virtual").
- `field_event_name_display` — whether the event name/year should be
  appended to the site name in the homepage title.
- `field_schedule_published`, `field_session_confirmation`,
  `field_session_deadline`, `field_sessions_open` — key CFP/scheduling
  deadlines, used both for editorial reference and to drive automatic
  state changes (see Editorial workflows below).

`event` terms move through the **Event Planning** workflow (`date` →
`sessions_open` → `sessions_closed` → `scheduled` → `underway` →
`published`), so the lifecycle of a conference edition — announce it, open
the call for proposals, close it, publish the schedule, run the event, wrap
up — is itself editorial content, not just a passive label.

A single **Event Details** config page (`/admin/event-details`) names the
site's "current" event (`field_current`) — this is the default event new
content is created against, and the one Smart Date fields and homepage
components fall back to when no event is specified in the URL.

Content types and taxonomies that reference `event`:

| Referencing entity | Field | Cardinality |
|---|---|---|
| `article` (node) | `field_event` | single, required |
| `bof` (node) | `field_event` | single, required |
| `session` (node) | `field_event` | single, required |
| `featured_speaker` (node) | `field_events` | multiple |
| `room` (taxonomy term) | `field_events` | multiple |
| `time_slot` (taxonomy term) | `field_event` | single |
| `sponsorship` (storage entity) | `field_events` | multiple |
| Event Details (config page) | `field_current` | single |


## Content types (nodes)

### Session (`session`)
The core CFP (call-for-proposals) content type — used for both regular
sessions and trainings. Key fields:

- `field_event` — the event this session was submitted to.
- `field_short_description` / `field_description` — abstract copy.
- `field_audience` → `session_audience` taxonomy (skill level/role facet).
- `field_session_category` → `session_category` taxonomy (topic/track).
- `field_r` → `room` taxonomy — the room it's scheduled in.
- `field_time_slot` → `time_slot` taxonomy — when it's scheduled. The field
  widget uses the `reference_view_time_slot_date_taxonomy` view as its
  selection handler, so editors pick a slot from a list sorted by actual
  date/time rather than an alphabetical term list.
- `field_speakers` → **user** entities (not nodes), filtered to accounts
  with the `speaker` role. Cardinality is unlimited for co-presented
  sessions.
- `field_is_training` / `field_is_non_session` — booleans that let the
  schedule distinguish trainings and non-session slots (lunch, opening
  remarks, etc.) from regular talks.

Sessions move through the **Session Acceptance** workflow: `proposed` →
`accepted`/`waitlisted`/`rejected`, then an accepted speaker `confirms` to
reach `published` ("Confirmed"), or `declines`. Session moderators score
proposals via the **Session Evaluation** webform (see below) before making
accept/reject decisions.

### BOF (`bof`)
"Birds of a Feather" informal session type. Shares the same scheduling
shape as `session` (`field_event`, `field_r`, `field_time_slot`) but is
simpler — no speaker, audience, or category fields, no moderation workflow.

### Featured Speaker (`featured_speaker`)
A promotional/marketing card for the homepage speaker showcase — **not**
the same thing as a `speaker`-role user account. Carries its own image,
`field_badge` (e.g. "Speaker" or "Keynote"), a manual `field_cta_link` to
the speaker's actual session, `field_events` (multi — a speaker can be
promoted across several editions), and `field_weight` to control display
order.

### Sponsor (`sponsor`)
A sponsoring company's profile: logo, website link, description. Notably,
the sponsor node carries **no tier/level field of its own** — see
Sponsorship (storage entity) below for how tiers are assigned.

### Job Listing (`job_listing`)
A job posting, always tied to a sponsor via `field_company` (required,
references a `sponsor` node). Carries `field_location` and an outbound
`field_url` to the actual posting — there's no in-site application form.

### Article (`article`)
Time-sensitive content — news, press releases, blog posts. Tied to
`field_event`, with `field_featured_image` and `field_tags` (→ `tags`
taxonomy, free-tagging). Uses the Scheduler module for timed
publish/unpublish, but no content moderation workflow.

### Page (`page`)
Plain static content (e.g. a Privacy Policy page). Moderated through the
**Basic** editorial workflow (draft → published → unpublished). This is
distinct from Canvas-built pages — see "Pages vs. Canvas pages" below.


## Taxonomies

| Vocabulary | Purpose | Notable term fields |
|---|---|---|
| `event` | One term per conference edition — the hub described above. | `field_dates`, `field_location_short`, plus CFP/schedule deadline dates |
| `room` | Physical or virtual session rooms, reusable across events. | `field_events` (multi) |
| `time_slot` | Discrete schedule slots. | `field_event` (single), `field_when` (Smart Date range, bounded by the current event's `field_dates`) |
| `session_audience` | Audience/skill-level facet for sessions. | — |
| `session_category` | Topic/track facet for sessions. | — |
| `sponsor_level` | Sponsorship tiers (e.g. Gold/Silver/Bronze). | — |
| `tags` | General free-tagging, used by `article`/`page`. | — |


## Custom entities: the `storage` type

The `drupal/storage` module provides a lightweight, generic content-entity
type used for a few small, reusable pieces of content that don't warrant a
full node type. Three bundles ship with this recipe:

- **Sponsorship** — the join record between a sponsor and a tier: `field_sponsor`
  (→ `sponsor` node), `field_level` (→ `sponsor_level` taxonomy), `field_events`
  (→ `event` taxonomy, multi). This indirection means the *same* sponsor node
  can be "Gold" at one event and "Platinum" at the next without editing the
  sponsor's own profile — the tier is a property of the sponsorship, not the
  sponsor. Manage these at `/admin/sponsorships`.
- **CTA** — a simple reusable link + label, for call-to-action content
  referenced from Canvas components. Manage at `/admin/ctas`.
- **Stat** — a simple text callout (e.g. "500+ attendees"), referenced from
  the Event Details config page's `field_stats` for homepage stat blocks.

All `storage` entities, across bundles, can also be reviewed together at
`/admin/content/storage`.


## Config pages

**Event Details** (`/admin/event-details`) is the single site-wide settings
object: which event is "current" (`field_current`), the display name shown
in the homepage hero, hero copy and CTA link, per-day program blurbs, and
the stat callouts shown on the homepage. Canvas homepage components and
several field defaults (like `time_slot`'s date bounds) read from this
config page.


## Webforms

- **Contact** — a generic site contact form, open to anyone.
- **Session Evaluation** — restricted to the `session_moderator` and
  `administrator` roles. Moderators pick a `session` node and score it on
  three Likert-scale questions (topic importance, originality, speaker
  authority). Submissions are unique per moderator/session, and feed the
  session moderation dashboard's sort order (see Views below).


## Editorial workflows

| Workflow | Applies to | States |
|---|---|---|
| **Basic** (`basic_editorial`) | `page` nodes | draft → published → unpublished |
| **Session Acceptance** | `session` nodes | proposed → accepted / waitlisted / rejected → confirmed (published) / declined |
| **Event Planning** | `event` taxonomy terms | date → sessions_open → sessions_closed → scheduled → underway → published ("Complete") |

The Event Planning workflow is designed to be advanced automatically as
deadlines pass: the `terms_update` view (see below) is a set of lookup
queries — one per transition — that find `event` terms sitting in a given
state whose relevant deadline field has already passed. Those queries back
this recipe's ECA (Event ­– Condition – Action) automations, which perform
the actual state transitions.


## Views

### Event hub & scheduling
- **Events** (`/events`) — public listing of all conference editions.
- **Event dates** — block showing an event's key dates, filtered to the
  current event term.
- **Event term** (`/events/%`) — an event's landing page: sticky `bof`/
  `session` content for that event, plus an RSS feed.
- **Terms update** — not a public display; five filtered lookups (one per
  Event Planning transition) used by ECA automation to find events ready to
  advance state.
- **Reference view: time slot date taxonomy** — the selection-handler view
  backing the `field_time_slot` widget on `session`/`bof`, so editors pick
  slots sorted by actual date/time.

### Sessions & schedule
- **Event Session List** — the workhorse view: the public schedule grid
  (`/events/%/schedule`), split displays for BOFs/sessions/trainings, and an
  editor-facing "manage" display with moderation state and room/slot
  columns.
- **BOFs** (`/events/%/schedule/bofs`) and **Training list**
  (`/events/%/trainings`) — filtered slices of the same content.
- **Concurrent sessions** — block on a session's page showing other
  sessions in the same room/time slot.
- **My schedule** (`/events/%/schedule/mine`) — an attendee's personally
  flagged/bookmarked sessions (via the Flag module).
- **Session speakers** — block listing speakers for a given session.
- **Sessions by state** — a simple "accepted sessions" block.

### CFP moderation & ratings
- **Session moderation** (`/sessions/moderation`) — the reviewer dashboard:
  proposed sessions sorted by evaluation score, with bulk accept/waitlist/
  reject actions and per-state attachment displays. Restricted to
  moderators/admins.
- **Session ratings** (`/sessions/ratings`) — a moderator's own scoring
  history.
- **Session evaluation** — block rendering a session's evaluation
  submissions.

### Sponsors & jobs
- **Event sponsors** — the tiered sponsor-logo wall for an event, joining
  `sponsorship` storage entities to their `sponsor` node and `sponsor_level`
  term. Rendered at `/events/%/sponsors` and as blocks.
- **Sponsorships** (`/admin/sponsorships`) — backend management list of
  sponsor/tier/event join records.
- **Job listings** (`/job-listings`) — public job board, joined back to the
  sponsoring company's current tier.

### Homepage & content
- **News** — per-event and homepage news blocks, filtered to `article`
  content tagged with the relevant event.
- **Featured speakers** — homepage speaker showcase, sorted by
  `field_weight`.
- **Canvas pages** / **Recent pages** — management views for Canvas-built
  pages (see below), distinct from the `content`/`recent_content` views
  which cover plain nodes.


## Pages vs. Canvas pages

The `page` node type (see above) is a conventional, field-based, moderated
content type for static pages. The **homepage**, and any other
component-built landing page, is a *different* entity type —
`canvas_page`, provided by the Canvas module — built visually from
Single Directory Components rather than fields. Don't confuse the two when
looking for "the homepage content": it won't be a `page` node, and it won't
appear in the `content` view; look under `/admin/content/pages` instead.


## Entity relationship summary

```
                         ┌───────────────┐
            ┌───────────▶│  event (term) │◀───────────-────┐
            │            └──────┬────────┘                 │
            │                   │                          │
  field_event (required)  field_events (multi)      field_current
            │                   │                          │
     ┌──────┴──────┐    ┌───────┴────────┐          ┌──────┴───────┐
     │article/bof/ │    │room, time_slot,│          │ Event Details│
     │session      │    │featured_speaker│          │ (config page)│
     └──────┬──────┘    │sponsorship     │          └──────────────┘
            │           └────────────────┘
   field_r, field_time_slot          field_stats
            │                              │
     ┌──────┴─────-─┐                ┌─────┴--──────┐
     │room/time_slot│                │ stat storage │
     └──────────-───┘                └────-─────────┘

sponsor (node) ◀── field_sponsor ── sponsorship (storage) ── field_level ──▶ sponsor_level (term)
   ▲
   │ field_company (required)
   │
job_listing (node)

session (node) ── field_speakers ──▶ user (role: speaker)
```
