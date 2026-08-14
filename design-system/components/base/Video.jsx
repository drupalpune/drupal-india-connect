import React from 'react';

export function Video({ src, poster, caption, autoplay = false, loop = false, muted = false, controls = true }) {
  const [playing, setPlaying] = React.useState(autoplay);
  const ref = React.useRef(null);

  const toggle = () => {
    const el = ref.current;
    if (!el) return;
    if (el.paused) { el.play(); setPlaying(true); } else { el.pause(); setPlaying(false); }
  };

  return (
    <figure className="eh-video">
      <video
        ref={ref}
        className="eh-video__el"
        src={src}
        poster={poster}
        autoPlay={autoplay}
        loop={loop}
        muted={muted || autoplay}
        aria-label={caption}
        playsInline
      >
        Your browser does not support the video tag.
      </video>

      {controls && (
        <div className="eh-video__controls">
          <button className="eh-video__play-pause" type="button" aria-label={playing ? 'Pause video' : 'Play video'} onClick={toggle}>
            <span className={`eh-video__icon-play${playing ? ' eh-video__icon--hidden' : ''}`}>
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true" focusable="false"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm40.77-88.4-48-32A8,8,0,0,0,108,96v64a8,8,0,0,0,12.77,6.4l48-32a8,8,0,0,0,0-12.8ZM124,141.1V114.9L145.56,128Z" /></svg>
            </span>
            <span className={`eh-video__icon-pause${playing ? '' : ' eh-video__icon--hidden'}`}>
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true" focusable="false"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216ZM112,96v64a8,8,0,0,1-16,0V96a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V96a8,8,0,0,1,16,0Z" /></svg>
            </span>
          </button>
        </div>
      )}

      {caption && <figcaption className="eh-video__caption">{caption}</figcaption>}
    </figure>
  );
}
