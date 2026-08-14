import React from 'react';

export function Card({
  headingText,
  text,
  media,
  mediaAlt = '',
  url,
  orientation = 'vertical',
  style = 'framed',
  background,
  centered = false,
  level = 3,
}) {
  const classes = [
    'eh-card',
    `eh-card--${orientation}`,
    `eh-card--${style}`,
    background ? `eh-card--bg-${background}` : null,
    centered ? 'eh-card--centered' : null,
    url ? 'eh-card--clickable' : null,
  ].filter(Boolean).join(' ');
  const Tag = `h${level}`;

  return (
    <div className={classes}>
      {media && (
        <div className="eh-card__media">
          <img className="eh-card__image" src={media} alt={mediaAlt} />
        </div>
      )}
      <div className="eh-card__body">
        {headingText && (
          <Tag className="eh-card__heading">
            {url ? <a href={url} className="eh-card__link">{headingText}</a> : headingText}
          </Tag>
        )}
        {text && <p className="eh-card__text">{text}</p>}
      </div>
    </div>
  );
}
