/**
 * @file
 * Sticky call to action behavior.
 *
 * Reveals the bar once the visitor has scrolled past the first screen, and
 * slides it away while another link to the same site (e.g. a ticket's
 * checkout button) is in view, so it never repeats a call to action the
 * visitor can already see. Uses
 * IntersectionObserver and scroll events, not requestAnimationFrame, so it
 * also works when the page loads in a background tab.
 */
(function (Drupal, once) {
  Drupal.behaviors.dacStickyCta = {
    attach(context) {
      once('dac-sticky-cta', '[data-dac-sticky-cta]', context).forEach((bar) => {
        if (!('IntersectionObserver' in window)) return;
        bar.hidden = false;

        const ownButton = bar.querySelector('a');
        // Other links to the same site as this button (e.g. each ticket's
        // KonfHub checkout), within the page body: when one is on screen the
        // bar would only repeat it.
        const host = ownButton ? new URL(ownButton.href, location.href).host : null;
        const scope = document.querySelector('main') || document;
        const others = [...scope.querySelectorAll('a[href]')].filter((a) => {
          if (bar.contains(a)) return false;
          try { return new URL(a.href, location.href).host === host; } catch { return false; }
        });
        // Step aside for the site footer too, so its links stay reachable.
        const footer = document.querySelector('.site-footer');
        if (footer) others.push(footer);
        const inView = new Set();
        const update = () => {
          const pastFirstScreen = window.scrollY > window.innerHeight * 0.75;
          const show = pastFirstScreen && inView.size === 0;
          bar.classList.toggle('is-offscreen', !show);
          // Keep the off-screen button out of the tab order.
          if (ownButton) ownButton.tabIndex = show ? 0 : -1;
          bar.setAttribute('aria-hidden', show ? 'false' : 'true');
        };

        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) inView.add(entry.target);
            else inView.delete(entry.target);
          });
          update();
        });
        others.forEach((el) => observer.observe(el));
        window.addEventListener('scroll', update, { passive: true });
        update();
      });
    },
  };
})(Drupal, once);
