/**
 * @file
 * Program days: turns the stacked days into tabs.
 *
 * Shows the tab bar, hides every day but one, and keeps the URL hash
 * (#day-1 …) in step so a day can be linked to. Any link to #day-N on the
 * page (e.g. a "See the Day 2 program" button) switches tab too.
 */
(function (Drupal, once) {
  Drupal.behaviors.dacProgramDays = {
    attach(context) {
      once('dac-program-days', '[data-program-days]', context).forEach((root) => {
        const tablist = root.querySelector('[role="tablist"]');
        const tabs = [...root.querySelectorAll('[role="tab"]')];
        const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
        const ids = panels.map((panel) => panel.id);

        tablist.hidden = false;
        panels.forEach((panel, i) => {
          panel.setAttribute('role', 'tabpanel');
          panel.setAttribute('aria-labelledby', tabs[i].id);
        });

        const select = (id, { focus = false, updateHash = true } = {}) => {
          tabs.forEach((tab, i) => {
            const on = ids[i] === id;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            panels[i].hidden = !on;
            if (on && focus) tab.focus();
          });
          if (updateHash) history.replaceState(null, '', `#${id}`);
        };

        tabs.forEach((tab, i) => {
          tab.addEventListener('click', () => select(ids[i]));
          tab.addEventListener('keydown', (event) => {
            const next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[event.key];
            if (next === undefined) return;
            event.preventDefault();
            select(ids[(next + tabs.length) % tabs.length], { focus: true });
          });
        });

        // In-page links to a day switch to it instead of jumping.
        document.addEventListener('click', (event) => {
          const link = event.target.closest('a[href*="#day-"]');
          if (!link || link.pathname !== window.location.pathname) return;
          const id = link.hash.slice(1);
          if (!ids.includes(id)) return;
          event.preventDefault();
          select(id, { focus: true });
          tablist.scrollIntoView({ behavior: 'instant', block: 'nearest' });
        });

        const fromHash = window.location.hash.slice(1);
        select(ids.includes(fromHash) ? fromHash : root.dataset.defaultDay, { updateHash: false });
      });
    },
  };
})(Drupal, once);
