import React from 'react';

export function SponsorTeaser({ name, logo, href, body }) {
  return (
    <article className="sponsor-teaser">
      <a className="sponsor-teaser__link" target="_blank" rel="noreferrer" href={href} aria-label={name}>
        <div className="sponsor-teaser__first">
          <div className="sponsor-teaser__img-wrapper">
            {logo && <img src={logo} alt={name} />}
          </div>
        </div>
        {body && <div className="field--name-body">{body}</div>}
      </a>
    </article>
  );
}
