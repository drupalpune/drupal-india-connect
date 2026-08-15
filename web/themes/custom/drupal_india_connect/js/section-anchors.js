/**
 * @file
 * Assigns anchor ids to the homepage's Canvas sections.
 *
 * The design's header nav is a set of same-page anchors (#about, #cfp,
 * #tickets, ...). Those ids cannot be authored as content: Canvas runs
 * every SDC rich-text prop through a filter that strips `id` attributes
 * (and drops <span>/<div>/<h2> outright), and event_horizon's `section`
 * component renders a bare `<section class="...">` with no attributes
 * object to hang an id on.
 *
 * So the ids are applied here instead, matched off each band's heading
 * text. The two views blocks (sponsors, news) already carry stable ids
 * of their own, so they are matched by their block class instead.
 *
 * Remove this once event_horizon's `section` exposes an id/anchor prop —
 * that is the correct home for this.
 */
((Drupal, once) => {
  // Heading text (lowercased, trimmed) => anchor id.
  const BY_HEADING = {
    'one drupal community, one continent': 'about',
    'why attend': 'why-attend',
    'the programme is yours to write': 'cfp',
    'ticket tiers': 'tickets',
    'iit bombay, mumbai': 'venue',
    'founding sponsors': 'sponsors',
    questions: 'faq',
    'be first to know': 'notify',
    news: 'news',
  };

  // Views block class => anchor id. Only used as a fallback: landing on the
  // whole band is better than landing on the listing, because the band's
  // heading tells you where you are.
  const BY_BLOCK_CLASS = {
    'block-views-blockevent-sponsors-block-3': 'sponsors',
    'block-views-blocknews-block-2': 'news',
  };

  /**
   * Publishes the sticky header's height so anchors can clear it.
   *
   * The header is sticky, so a link that scrolls its target to viewport top
   * puts the target's heading behind the bar. CSS needs the height to offset
   * against, and it changes with viewport (the nav wraps) — so measure it
   * rather than hard-coding a value that is only right at one width.
   *
   * Also publishes --dac-mobile-nav-offset: the mobile drawer
   * (event_horizon's .menu--main) reserves a static 7.5rem of top padding
   * to clear the header, but this theme adds an announcement bar above the
   * header and makes the header itself taller/sticky, so 7.5rem often isn't
   * enough — the drawer's first row (and its has-children toggle) then
   * renders underneath the still-visible header, which sits above the
   * drawer in stacking order and swallows the tap. getBoundingClientRect()
   * .bottom (not .height) is what's needed here: at the top of the page it
   * already includes the announcement bar's height, because the header
   * follows it in normal flow; once scrolled past, the bar is gone and the
   * header is pinned at top:0, so .bottom collapses to just its own height.
   * Recomputed on scroll too, since that value changes continuously while
   * the header settles into its stuck position.
   */
  const trackHeaderHeight = () => {
    const header = document.querySelector('.site-header');
    if (!header) {
      return;
    }
    const publish = () => {
      const rect = header.getBoundingClientRect();
      document.documentElement.style.setProperty(
        '--dac-header-height',
        `${Math.round(rect.height)}px`,
      );
      document.documentElement.style.setProperty(
        '--dac-mobile-nav-offset',
        `${Math.round(rect.bottom)}px`,
      );
    };
    publish();
    window.addEventListener('scroll', publish, { passive: true });
    if (window.ResizeObserver) {
      new ResizeObserver(publish).observe(header);
    } else {
      window.addEventListener('resize', publish);
    }
  };

  Drupal.behaviors.dacSectionAnchors = {
    attach(context) {
      once('dac-section-anchors', 'body', context).forEach(() => {
        trackHeaderHeight();

        // Marks an element as something a nav link scrolls to, so the CSS
        // can give it room to clear the sticky header.
        const anchor = (element, id) => {
          element.id = id;
          element.classList.add('dac-anchor-target');
        };

        document.querySelectorAll('.eh-section').forEach((section) => {
          if (section.id) {
            return;
          }
          const heading = section.querySelector('h1, h2, h3');
          if (!heading) {
            return;
          }
          const id = BY_HEADING[heading.textContent.trim().toLowerCase()];
          if (id && !document.getElementById(id)) {
            anchor(section, id);
          }
        });

        Object.entries(BY_BLOCK_CLASS).forEach(([className, id]) => {
          const block = document.querySelector(`.${className}`);
          if (block && !document.getElementById(id)) {
            anchor(block, id);
          }
        });

        // The browser resolves the URL fragment before this runs, so a deep
        // link like /#tickets lands at the top of the page. Re-resolve it
        // now that the targets exist.
        //
        // This has to wait for the load event: running it earlier starts a
        // scroll that the browser's own end-of-load scroll handling cancels,
        // leaving you at the top.
        //
        // 'instant', not 'auto' — 'auto' defers to the CSS scroll-behavior,
        // which base.css sets to smooth, so the jump would animate all the
        // way down the page and be cancellable. Arriving on a deep link
        // should just put you there.
        const hash = window.location.hash.slice(1);
        if (!hash) {
          return;
        }
        // Not requestAnimationFrame: frames are paused while the tab is in
        // the background, so a link opened in a new tab would never resolve.
        const resolve = () => {
          const target = document.getElementById(hash);
          if (target) {
            target.scrollIntoView({ behavior: 'instant' });
          }
        };
        if (document.readyState === 'complete') {
          resolve();
        } else {
          window.addEventListener('load', resolve, { once: true });
        }
      });
    },
  };
})(Drupal, once);
