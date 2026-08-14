import React from 'react';

export function Cta({
  headingText,
  text,
  actions,
  level = 2,
  textAlign = 'center',
  backgroundColor,
  media,
  mediaAlt = '',
  overlayOpacity = '20%',
}) {
  const classes = ['eh-cta', `eh-cta--align-${textAlign}`, backgroundColor ? `eh-cta--bg-${backgroundColor}` : null].filter(Boolean).join(' ');
  const Tag = `h${level}`;

  return (
    <section className={classes}>
      {media && (
        <div className="eh-cta__bg">
          <div className={`eh-cta__overlay eh-cta__overlay--opacity-${String(overlayOpacity).replace('%', '')}`} />
          <img className="eh-cta__bg-image" src={media} alt={mediaAlt} />
        </div>
      )}
      <div className="eh-cta__inner">
        {headingText && <Tag className="eh-cta__heading">{headingText}</Tag>}
        {text && <p className="eh-cta__text">{text}</p>}
        <div className="eh-cta__actions">{actions}</div>
      </div>
    </section>
  );
}
