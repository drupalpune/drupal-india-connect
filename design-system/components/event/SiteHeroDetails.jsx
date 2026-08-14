import React from 'react';

export function SiteHeroDetails({ dates, location, days = [] }) {
  const [open, setOpen] = React.useState(false);

  return (
    <div className="site-hero-details">
      {dates && <span className="site-hero-details__item">{dates}</span>}
      {location && <span className="site-hero-details__item">{location}</span>}
      <button className="site-hero-details__button" aria-expanded={open} onClick={() => setOpen((v) => !v)}>
        <div className="visually-hidden">Show event details</div>
      </button>
      {days.length > 0 && (
        <div className="site-hero-details__flyout">
          <div className="site-hero-details__flyout-general">
            <div className="site-hero-details__flyout-general-date">{dates}</div>
            <div className="site-hero-details__flyout-general-location">{location}</div>
          </div>
          {days.map((day, i) => (
            <div className="site-hero-details__flyout-day" key={day.label || i}>
              <div className="site-hero-details__flyout-day-heading">{day.label || `Day ${i + 1}`}</div>
              <div className="site-hero-details__flyout-day-info">{day.info}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
