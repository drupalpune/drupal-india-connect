import React from 'react';

export function CardLogo({ media, mediaAlt = '', url, backgroundColor, borderRadius }) {
  const classes = [
    'eh-card-logo',
    backgroundColor ? `eh-card-logo--bg-${backgroundColor}` : null,
    borderRadius ? `eh-card-logo--radius-${borderRadius}` : null,
    url ? 'eh-card-logo--linked' : null,
  ].filter(Boolean).join(' ');

  const img = media ? <img className="eh-card-logo__img" src={media} alt={mediaAlt} /> : null;

  return (
    <div className={classes}>
      <div className="eh-card-logo__inner">
        {url ? <a href={url} className="eh-card-logo__link">{img}</a> : img}
      </div>
    </div>
  );
}
