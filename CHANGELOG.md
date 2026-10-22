# Changelog

What changed on [drupalasia.org](https://drupalasia.org), day by day, in plain
language. Newest first.

A new entry is written every night at 23:00 IST by the
[Daily changelog](.github/workflows/changelog.yml) workflow from that day's
commits, releases, merged pull requests and closed issues. Something is only
live once it ships in a release tag (see "Live on the site").

## 2026-10-23 (Friday)

### Changed on the site
- The public sitemap now lists every page under **www.drupalasia.org** instead of drupalasia.org. The same pages appear on both lists, so no pages were actually added or removed; only the web address form in the sitemap changed. ([page](https://www.drupalasia.org/))

## 2026-10-21 (Wednesday)

### New on the site
- A new session page was added: "Building Intelligent Public Infrastructure: Open Source, Drupal, and AI". ([page](https://drupalasia.org/ai-summit/building-intelligent-public-infrastructure))

## 2026-10-16 (Friday)

### New on the site
- A new **Meet the Volunteers** page was added. ([page](https://drupalasia.org/meet-the-volunteers))

## 2026-10-15 (Thursday)

### Changed on the site
- The **Tickets & Rates** page was updated. ([page](https://drupalasia.org/ticket-rates-and-details))
- Two session pages were updated, including "Terminal-First Drupal: Engineering Architecture with Claude Code Agents" and "Zero-Surprise Drupal: How AI-Powered Proactive Auditing Transformed TAM Workflows and Boosted Customer Retention". ([page](https://drupalasia.org/events/india-2027/sessions/terminal-first-drupal-engineering-architecture-claude-code-agents))

### Merged, not live yet
- The nightly changelog now includes public page changes from the site, and its publishing step was fixed so the finished entry is saved correctly. This affects only this changelog, not the website. ([#122](https://github.com/drupalpune/drupal-india-connect/pull/122) · [#123](https://github.com/drupalpune/drupal-india-connect/pull/123))

### Needs attention
- After the fix in #123 was merged, the Daily changelog needs to be re-run by hand from the Actions tab with the date 2026-10-15, since it could not be tested before merging. ([#123](https://github.com/drupalpune/drupal-india-connect/pull/123))

## 2026-10-14 (Wednesday)

Live on the site: 1.3.9, 1.3.10, 1.3.11, 1.3.12, 1.3.13

### New on the site
- **Meet the Committees** page under Community. The first committee is Registration Leads; more can be added in Canvas or by sending names in Slack. ([page](https://drupalasia.org/meet-the-committees) · [#120](https://github.com/drupalpune/drupal-india-connect/issues/120))
- "Save the date" in the orange top banner now opens Google Calendar with the event filled in. ([#100](https://github.com/drupalpune/drupal-india-connect/issues/100) · [#112](https://github.com/drupalpune/drupal-india-connect/pull/112))

### Changed on the site
- **Tickets & Registration** page redesigned: hero photo, Early Bird countdown, simpler ticket layout and a centred FAQ. Early Bird now runs until 10 December 2026. ([page](https://drupalasia.org/ticket-rates-and-details) · [#111](https://github.com/drupalpune/drupal-india-connect/issues/111))
- **Meet the Team** is now **Meet the Organizers**. The old link redirects to the new one. ([page](https://drupalasia.org/meet-the-organizers) · [#120](https://github.com/drupalpune/drupal-india-connect/issues/120))
- On phones, narrow sections (like the FAQs) now use the full screen width, so questions no longer wrap over several lines. ([#114](https://github.com/drupalpune/drupal-india-connect/pull/114))

### For editors and moderators
- Sessions have a new **Programme** field to mark a talk as **AI Summit**. The session page shows it, and the session management list can be filtered by it. Leaving it empty means main conference. ([#88](https://github.com/drupalpune/drupal-india-connect/issues/88))

### Behind the scenes
- Restored the "Program team" pattern in the Canvas editor, which the 1.3.9 deploy had removed. ([#115](https://github.com/drupalpune/drupal-india-connect/pull/115))

### Needs attention
- KonfHub's Early Bird end date must also be moved to 10 December; the site only displays the date. ([#116](https://github.com/drupalpune/drupal-india-connect/pull/116))
