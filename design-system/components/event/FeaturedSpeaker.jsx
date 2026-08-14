import React from 'react';

export function FeaturedSpeaker({ name, image, imageAlt = '', badge, body, href }) {
  return (
    <article className="featured-speaker">
      <a className="featured-speaker__link" href={href}>
        <div className="featured-speaker__img">
          {image && <img src={image} alt={imageAlt || name} />}
          {badge && <div className="featured-speaker__badge">{badge}</div>}
        </div>
        <h3 className="featured-speaker__title">{name}</h3>
        {body && <div className="featured-speaker__desc"><div className="text-content">{body}</div></div>}
      </a>
    </article>
  );
}
