import React from 'react';

export function SiteStats({ title = 'Highlights', stats = [] }) {
  if (!stats.length) return null;
  return (
    <section aria-label="Statistics" className="site-stats">
      <div className="site-stats__inner container grid">
        <div className="site-stats__label">
          <h2 className="site-stats__title">{title}</h2>
        </div>
        <ul className="site-stats__list">
          {stats.map((s, i) => (
            <li className="site-stats__item" key={s.stat + i}>
              <div className="site-stats__stat">{s.stat}</div>
              <div className="site-stats__desc">{s.desc}</div>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}
