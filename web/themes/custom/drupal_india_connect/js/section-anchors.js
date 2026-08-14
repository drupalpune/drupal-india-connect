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
    'host city announcement soon': 'venue',
    'founding sponsors': 'sponsors',
    questions: 'faq',
    'be first to know': 'notify',
  };

  // Views block class => anchor id.
  const BY_BLOCK_CLASS = {
    'block-views-blockevent-sponsors-block-3': 'sponsors',
    'block-views-blocknews-block-2': 'news',
  };

  Drupal.behaviors.dacSectionAnchors = {
    attach(context) {
      once('dac-section-anchors', 'body', context).forEach(() => {
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
            section.id = id;
          }
        });

        Object.entries(BY_BLOCK_CLASS).forEach(([className, id]) => {
          const block = document.querySelector(`.${className}`);
          if (block && !document.getElementById(id)) {
            block.id = id;
          }
        });

        // The browser resolves the URL fragment before this runs, so a deep
        // link like /#tickets lands at the top of the page. Re-resolve it
        // now that the targets exist.
        const hash = window.location.hash.slice(1);
        if (hash) {
          const target = document.getElementById(hash);
          if (target) {
            target.scrollIntoView();
          }
        }
      });
    },
  };
})(Drupal, once);
