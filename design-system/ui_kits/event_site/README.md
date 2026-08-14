# UI kit — event site

A click-through recreation of the DrupalAsia Connect marketing site, composed
entirely from this design system's components. Four screens, switched by the
navbar:

| Screen | File | What it shows |
| --- | --- | --- |
| Home | `Home.jsx` | `SiteHero25` → countdown band → about → why attend → CFP timeline → tickets → venue → FAQ → newsletter CTA |
| Speakers | `Speakers.jsx` | The featured-speakers row on its dark band, with the duotone portrait treatment, plus session tracks as badges |
| Schedule | `Schedule.jsx` | The schedule layout: sticky time slots in the left column, sessions right, primary-rule dividers |
| Sponsors | `Sponsors.jsx` | Tiered sponsor grids using `SponsorTeaser`, a stats band, and the prospectus CTA |

`Shell.jsx` holds the announcement bar, navbar and footer shared by all four.

## Notes

- The screens use only components from this system — no bespoke markup, apart
  from a handful of inline-styled rows (the CFP milestone list) that the base
  theme has no component for.
- Speakers, schedule and sponsor content is **placeholder**: the event is at
  the save-the-date stage, so no real speakers, sessions or sponsors exist yet.
  Portraits and logos stand in with theme-supplied artwork.
- The homepage hero has no teaser video yet, so `SiteHero25` renders its
  poster path instead. Drop a real 1:1 video in and pass `videoSrc`.
- Ticket tiers deliberately carry no prices — see `components/cards/Card.prompt.md`
  and the root readme's note on `card-pricing`.
