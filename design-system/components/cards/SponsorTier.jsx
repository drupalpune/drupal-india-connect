import React from 'react';

const ACCENT_VAR = {
  secondary: 'var(--secondary)',
  'primary-50': 'var(--color--primary-50)',
  'primary-60': 'var(--color--primary-60)',
  neutral: 'var(--color--night-50)',
};

export function SponsorTier({
  tierName,
  price,
  benefits,
  tierAccent = 'primary-60',
  ctaText = 'Become a sponsor',
  ctaUrl,
}) {
  const accent = ACCENT_VAR[tierAccent] || ACCENT_VAR['primary-60'];

  return (
    <article className="sponsor-tier" style={{ '--tier-accent': accent }}>
      <header className="sponsor-tier__header">
        {tierName && <p className="sponsor-tier__name">{tierName}</p>}
        {price && <p className="sponsor-tier__price">{price}</p>}
      </header>
      {benefits && (
        <div className="sponsor-tier__benefits" dangerouslySetInnerHTML={{ __html: benefits }} />
      )}
      {ctaUrl && (
        <a className="sponsor-tier__cta" href={ctaUrl}>{ctaText}</a>
      )}
    </article>
  );
}
