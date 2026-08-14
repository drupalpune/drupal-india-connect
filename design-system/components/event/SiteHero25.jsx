import React from 'react';
import { SiteHeroDetails } from './SiteHeroDetails.jsx';

export function SiteHero25({
  eventName,
  description,
  ctaLabel,
  ctaHref,
  dates,
  location,
  days,
  videoSrc,
  videoType,
  posterSrc,
  graphicSrc,
  textureSrc,
}) {
  const [paused, setPaused] = React.useState(false);
  const ref = React.useRef(null);
  const style = {};
  if (graphicSrc) { style['--graphic-path'] = `url(${graphicSrc})`; style['--graphic-bkgd'] = 'var(--neutral-dark)'; }
  if (textureSrc) style['--texture-path'] = `url(${textureSrc})`;

  const toggle = () => {
    const el = ref.current;
    if (!el) return;
    if (el.paused) { el.play(); setPaused(false); } else { el.pause(); setPaused(true); }
  };

  return (
    <div className="site-hero" style={style}>
      <div className="site-hero__container container grid">
        <h1 className="site-hero__title">{eventName}</h1>
        {description && <div className="site-hero__desc">{description}</div>}
        {ctaLabel && (
          <div className="site-hero__cta">
            <a href={ctaHref} className="site-hero__media-cta">
              {ctaLabel}
              <div className="site-hero__media-cta-icon" />
            </a>
          </div>
        )}
        <SiteHeroDetails dates={dates} location={location} days={days} />
        <div className="site-hero__media">
          {videoSrc ? (
            <>
              <button className="site-hero__play-pause" type="button" aria-pressed={paused} onClick={toggle}>
                {paused ? 'Play' : 'Pause'}
              </button>
              <video ref={ref} loop autoPlay muted playsInline poster={posterSrc} width="544" height="480">
                <source src={videoSrc} type={videoType} />
              </video>
            </>
          ) : posterSrc ? (
            <img src={posterSrc} alt="" className="site-hero__poster" width="544" height="480" />
          ) : null}
        </div>
      </div>
    </div>
  );
}
