/**
 * @file
 * Dismisses the "fill in your profile" nudge on a user's own account page.
 *
 * The nudge (see _drupal_india_connect_profile_nudge() in
 * drupal_india_connect.theme) has no server-persisted "dismissed" state —
 * it always renders when either field is blank, and this behavior hides it
 * client-side instead, remembering the dismissal per uid in localStorage so
 * it stays hidden on later visits from the same browser.
 */
((Drupal, once) => {
  const storageKey = (uid) => `dac-profile-nudge-dismissed-${uid}`;

  Drupal.behaviors.dacProfileNudge = {
    attach(context) {
      once('dac-profile-nudge', '#dac-profile-nudge', context).forEach((nudge) => {
        const uid = nudge.dataset.uid;
        let dismissed = false;
        try {
          dismissed = window.localStorage.getItem(storageKey(uid)) === '1';
        } catch (e) {
          // localStorage unavailable (private browsing, etc.) — just show
          // the nudge every time rather than failing to render it.
        }

        if (dismissed) {
          nudge.remove();
          return;
        }

        const dismissButton = nudge.querySelector('.dac-profile-nudge__dismiss');
        if (!dismissButton) {
          return;
        }
        dismissButton.addEventListener('click', () => {
          try {
            window.localStorage.setItem(storageKey(uid), '1');
          } catch (e) {
            // Nothing to persist to; the nudge still hides for this view.
          }
          nudge.remove();
        });
      });
    },
  };
})(Drupal, once);
