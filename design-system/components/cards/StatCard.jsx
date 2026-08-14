import React from 'react';

export function StatCard({ stat, description }) {
  return (
    <div className="eh-stat-card">
      {stat && <div className="eh-stat-card__stat">{stat}</div>}
      {description && <div className="eh-stat-card__description">{description}</div>}
    </div>
  );
}
