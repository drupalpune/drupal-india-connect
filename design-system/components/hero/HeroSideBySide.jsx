import React from 'react';

export function HeroSideBySide({
  children,
  media,
  mediaAlt = '',
  imagePosition = 'left',
  imageSize = '4:3',
  imageRadius = 'small',
  gap = 'large',
  mobileDirection = 'vertical',
  justify = 'center',
  paddingBlockStart = '0',
  paddingBlockEnd = '0',
}) {
  const classes = [
    'eh-hero-sbs',
    `eh-hero-sbs--img-${imagePosition}`,
    `eh-hero-sbs--mobile-${mobileDirection}`,
    `eh-hero-sbs--gap-${gap}`,
    paddingBlockStart !== '0' ? `eh-hero-sbs--pt-${paddingBlockStart}` : null,
    paddingBlockEnd !== '0' ? `eh-hero-sbs--pb-${paddingBlockEnd}` : null,
  ].filter(Boolean).join(' ');

  return (
    <section className={classes}>
      <div className={`eh-hero-sbs__media eh-hero-sbs__media--ratio-${imageSize.replace(':', '-')} eh-hero-sbs__media--radius-${imageRadius}`}>
        {media && <img className="eh-hero-sbs__image" src={media} alt={mediaAlt} loading="eager" />}
      </div>
      <div className={`eh-hero-sbs__content eh-hero-sbs__content--justify-${justify}`}>{children}</div>
    </section>
  );
}
