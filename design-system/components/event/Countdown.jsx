import React from 'react';

const pad = (n) => String(n).padStart(2, '0');

export function Countdown({
  target,
  eyebrow,
  label,
  finishedText = 'We are underway.',
  variant = 'dark',
}) {
  const targetMs = React.useMemo(() => Date.parse(target), [target]);
  const [remaining, setRemaining] = React.useState(() => Math.max(0, targetMs - Date.now()));

  React.useEffect(() => {
    if (Number.isNaN(targetMs)) return undefined;
    const id = setInterval(() => setRemaining(Math.max(0, targetMs - Date.now())), 1000);
    return () => clearInterval(id);
  }, [targetMs]);

  const done = !Number.isNaN(targetMs) && remaining <= 0;
  const secs = Math.floor(remaining / 1000);
  const units = [
    { key: 'days', label: 'Days', value: String(Math.floor(secs / 86400)) },
    { key: 'hours', label: 'Hours', value: pad(Math.floor((secs % 86400) / 3600)) },
    { key: 'minutes', label: 'Minutes', value: pad(Math.floor((secs % 3600) / 60)) },
    { key: 'seconds', label: 'Seconds', value: pad(secs % 60) },
  ];

  return (
    <div className={`eh-countdown eh-countdown--${variant}`}>
      <div className="eh-countdown__intro">
        {eyebrow && <span className="eh-countdown__eyebrow">{eyebrow}</span>}
        {label && <span className="eh-countdown__label">{label}</span>}
      </div>

      {!done && (
        <div className="eh-countdown__units" role="timer" aria-live="off">
          {units.map((u) => (
            <div className={`eh-countdown__unit eh-countdown__unit--${u.key}`} key={u.key}>
              <span className="eh-countdown__value">{u.value}</span>
              <span className="eh-countdown__unit-label">{u.label}</span>
            </div>
          ))}
        </div>
      )}

      {done && <p className="eh-countdown__finished">{finishedText}</p>}
    </div>
  );
}
