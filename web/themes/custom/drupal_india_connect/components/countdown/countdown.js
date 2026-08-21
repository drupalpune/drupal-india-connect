/**
 * @file
 * Countdown behavior.
 *
 * Ticks once a second and writes the remaining days/hours/minutes/seconds
 * into the pre-rendered markup. When the target has passed, the digits are
 * replaced by the finished message.
 */
(function (Drupal, once) {
  const pad = (n) => String(n).padStart(2, '0');

  Drupal.behaviors.ehCountdown = {
    attach(context) {
      once('eh-countdown', '[data-eh-countdown]', context).forEach((el) => {
        const target = Date.parse(el.dataset.target);
        if (Number.isNaN(target)) return;

        const units = el.querySelector('.eh-countdown__units');
        const finished = el.querySelector('[data-eh-countdown-finished]');
        const out = {};
        el.querySelectorAll('[data-eh-countdown-unit]').forEach((node) => {
          out[node.dataset.ehCountdownUnit] = node;
        });

        function complete() {
          if (units) units.hidden = true;
          if (finished) {
            finished.textContent = el.dataset.finishedText || '';
            finished.hidden = false;
          }
        }

        function tick() {
          const remaining = target - Date.now();
          if (remaining <= 0) {
            complete();
            clearInterval(el.ehCountdownInterval);
            return;
          }
          const secs = Math.floor(remaining / 1000);
          if (out.days) out.days.textContent = String(Math.floor(secs / 86400));
          if (out.hours) out.hours.textContent = pad(Math.floor((secs % 86400) / 3600));
          if (out.minutes) out.minutes.textContent = pad(Math.floor((secs % 3600) / 60));
          if (out.seconds) out.seconds.textContent = pad(secs % 60);
        }

        // Store the interval id on the element (not a local const) so that
        // a target already in the past on the first synchronous tick()
        // call below doesn't clearInterval() a binding that hasn't been
        // assigned yet.
        tick();
        el.ehCountdownInterval = setInterval(tick, 1000);
      });
    },
  };
})(Drupal, once);
