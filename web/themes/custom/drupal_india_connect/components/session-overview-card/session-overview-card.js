/**
 * @file
 * Session overview card: animates the "Read full summary" disclosure.
 *
 * The summary is a native <details>, so it opens and closes without this
 * script, and with prefers-reduced-motion it is left alone. Otherwise the
 * body's height is animated over 0.4s, the accordion's duration in the
 * design system. While closing, .is-closing flips the label and arrow at
 * once rather than when the animation ends.
 */
(function (Drupal, once) {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  Drupal.behaviors.dacSessionSummary = {
    attach(context) {
      once('dac-session-summary', '.session-overview-card__summary', context).forEach((details) => {
        const toggle = details.querySelector('summary');
        const body = details.querySelector('.session-overview-card__summary-body');
        let animation = null;

        toggle.addEventListener('click', (event) => {
          if (reduceMotion.matches || !body.animate) {
            return;
          }
          event.preventDefault();

          const opening = !details.open || details.classList.contains('is-closing');
          const from = details.open ? body.offsetHeight : 0;
          if (animation) {
            animation.cancel();
          }
          details.classList.toggle('is-closing', !opening);
          if (opening) {
            details.open = true;
          }
          const to = opening ? body.scrollHeight : 0;

          animation = body.animate(
            [
              { height: `${from}px`, opacity: opening ? 0.4 : 1 },
              { height: `${to}px`, opacity: opening ? 1 : 0 },
            ],
            { duration: 400, easing: 'ease' },
          );
          animation.onfinish = () => {
            animation = null;
            if (!opening) {
              details.open = false;
              details.classList.remove('is-closing');
            }
          };
        });
      });
    },
  };
})(Drupal, once);
