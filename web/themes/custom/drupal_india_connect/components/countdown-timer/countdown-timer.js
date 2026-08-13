((Drupal, once) => {
  const MS_PER_SECOND = 1000;
  const MS_PER_MINUTE = 60 * MS_PER_SECOND;
  const MS_PER_HOUR = 60 * MS_PER_MINUTE;
  const MS_PER_DAY = 24 * MS_PER_HOUR;

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function render(el, remainingMs) {
    const days = Math.floor(remainingMs / MS_PER_DAY);
    const hours = Math.floor((remainingMs % MS_PER_DAY) / MS_PER_HOUR);
    const minutes = Math.floor((remainingMs % MS_PER_HOUR) / MS_PER_MINUTE);
    const seconds = Math.floor((remainingMs % MS_PER_MINUTE) / MS_PER_SECOND);

    const values = { days, hours, minutes, seconds };
    Object.keys(values).forEach((unit) => {
      const target = el.querySelector(`[data-countdown-unit="${unit}"]`);
      if (target) {
        target.textContent = pad(values[unit]);
      }
    });
  }

  function init(el) {
    const targetTime = Date.parse(el.dataset.countdownTarget);
    const expiredMessage = el.querySelector(
      '.countdown-timer__expired-message',
    );

    if (Number.isNaN(targetTime)) {
      return;
    }

    function tick() {
      const remainingMs = targetTime - Date.now();

      if (remainingMs <= 0) {
        render(el, 0);
        el.classList.add('countdown-timer--expired');
        if (expiredMessage) {
          expiredMessage.hidden = false;
        }
        clearInterval(el.countdownTimerInterval);
        return;
      }

      render(el, remainingMs);
    }

    tick();
    el.countdownTimerInterval = setInterval(tick, MS_PER_SECOND);
  }

  Drupal.behaviors.countdownTimer = {
    attach(context) {
      once('countdown-timer', '.countdown-timer', context).forEach(init);
    },
  };
})(Drupal, once);
