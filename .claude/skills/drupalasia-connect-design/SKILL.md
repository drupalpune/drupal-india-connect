---
name: drupalasia-connect-design
description: Use this skill to generate well-branded interfaces and assets for DrupalAsia Connect, either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping.
user-invocable: true
---

The design system lives at `design-system/` in the repository root, not
inside this skill directory — it is a reference for everyone working on the
project, not only for Claude. Read `design-system/readme.md` first, then
explore the rest.

| Path | What is there |
| --- | --- |
| `design-system/readme.md` | The system, its sources, and the rules. Start here. |
| `design-system/tokens/` | Colour, type, spacing tokens. |
| `design-system/guidelines/` | Standalone HTML cards, one per topic — open them in a browser. |
| `design-system/components/` | Component patterns. |
| `design-system/ui_kits/event_site/` | Assembled page-level kits. |
| `design-system/assets/` | Fonts, icons, textures. |
| `design-system/reference/` | The approved homepage prototype the build was matched against. |

If creating visual artifacts (slides, mocks, throwaway prototypes), copy
assets out and create static HTML files for the user to view. If working on
production code, read the rules here to become an expert in designing with
this brand. If the user invokes this skill without any other guidance, ask
them what they want to build or design, ask some questions, and act as an
expert designer who outputs HTML artifacts _or_ production code, depending
on the need.

Three things specific to this brand and this repository, before you design
anything:

1. **Never hardcode the brand hexes.** Primary and secondary are derived
   ramps — set the hue/saturation/lightness triples in
   `design-system/tokens/colors.css` and use `var(--color--primary-*)` /
   `var(--color--secondary-*)`.
2. **The target platform is Drupal 11** with the `event_horizon` theme,
   Canvas page building, and the event platform modules. If the work is
   going into the real site, it is almost always configuration plus an SDC —
   not new markup. Read the Components section of
   `design-system/readme.md` for what already exists.
3. **The live theme is the source of truth, not this system.** The build has
   moved past the handoff in places: the hero is set in the logo's sentence
   case rather than the system's all-caps display type, and the homepage
   hero is a custom component. Check
   `web/themes/custom/drupal_india_connect/` before assuming a pattern here
   is what ships. See `CLAUDE.md` for the traps in this codebase.
