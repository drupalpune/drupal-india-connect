/**
 * @file
 * Dismisses the "fill in your Drupal.org / Slack username" account nudge.
 *
 * The nudge only reappears until the visitor dismisses it once — dismissal
 * is stored in localStorage per uid (not per-field), since re-visiting the
 * account page after filling in just one of the two fields would otherwise
 * show it again for the other. No server round-trip, matching the "cheapest
 * option" scope agreed in issue #41 (no email/notification infra).
 */
((Drupal, once) => {
  Drupal.behaviors.dacProfileNudge = {
    attach(context) {
      once('dac-profile-nudge', '[data-dac-profile-nudge]', context).forEach((el) => {
        const key = `dac-profile-nudge-dismissed-${el.getAttribute('data-dac-profile-nudge')}`;

        if (window.localStorage.getItem(key) === '1') {
          el.remove();
          return;
        }

        el.querySelector('.dac-profile-nudge__dismiss')?.addEventListener('click', () => {
          window.localStorage.setItem(key, '1');
          el.remove();
        });
      });
    },
  };
})(Drupal, once);
