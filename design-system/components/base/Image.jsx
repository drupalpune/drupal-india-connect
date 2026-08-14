import React from 'react';

const RATIOS = { '2:1': '2-1', '16:9': '16-9', '3:2': '3-2', '4:3': '4-3', '1:1': '1-1' };

export function Image({ src, alt = '', ratio = '4:3', radius, url, caption }) {
  const classes = [
    'eh-image__container',
    `eh-image--ratio-${RATIOS[ratio] || '4-3'}`,
    radius ? `eh-image--radius-${radius}` : null,
    url ? 'eh-image--linked' : null,
  ].filter(Boolean).join(' ');

  const img = <img className="eh-image__img" src={src} alt={alt} />;

  return (
    <figure className="eh-image">
      <div className={classes}>
        {url ? <a className="eh-image__link" href={url}>{img}</a> : img}
      </div>
      {caption && <figcaption className="eh-image__caption">{caption}</figcaption>}
    </figure>
  );
}
