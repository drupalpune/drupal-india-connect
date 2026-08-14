import React from 'react';

export function HeroBillboard({
  children,
  media,
  mediaAlt = '',
  overlayOpacity = '0%',
  objectPosition = 'center',
  height = 'full',
  flexPosition = 'center-left',
  overlapNavbar = false,
}) {
  const classes = [
    'eh-hero-billboard',
    `eh-hero-billboard--height-${height}`,
    `eh-hero-billboard--pos-${flexPosition}`,
    overlapNavbar ? 'eh-hero-billboard--overlap-navbar' : null,
  ].filter(Boolean).join(' ');

  return (
    <section className={classes}>
      {media && (
        <div className="eh-hero-billboard__bg">
          <div className={`eh-hero-billboard__overlay eh-hero-billboard__overlay--opacity-${String(overlayOpacity).replace('%', '')}`} />
          <img className={`eh-hero-billboard__bg-image eh-hero-billboard__bg-image--${objectPosition}`} src={media} alt={mediaAlt} loading="eager" />
        </div>
      )}
      <div className="eh-hero-billboard__inner">
        <div className="eh-hero-billboard__content">{children}</div>
      </div>
    </section>
  );
}
