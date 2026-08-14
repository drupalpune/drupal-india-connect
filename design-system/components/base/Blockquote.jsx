import React from 'react';

export function Blockquote({ quote, name, cite, citeUrl, showMark = true }) {
  return (
    <figure className="eh-blockquote">
      {showMark && (
        <svg className="eh-blockquote__mark" viewBox="0 0 256 256" fill="currentColor" aria-hidden="true" focusable="false">
          <path d="M100,56H40A16,16,0,0,0,24,72v64a16,16,0,0,0,16,16H84v8a36,36,0,0,1-36,36,8,8,0,0,0,0,16,52.06,52.06,0,0,0,52-52V72A16,16,0,0,0,100,56Zm116,0H156a16,16,0,0,0-16,16v64a16,16,0,0,0,16,16h44v8a36,36,0,0,1-36,36,8,8,0,0,0,0,16,52.06,52.06,0,0,0,52-52V72A16,16,0,0,0,216,56Z" />
        </svg>
      )}
      <div className="eh-blockquote__body">
        <blockquote className="eh-blockquote__text">{quote}</blockquote>
        <figcaption className="eh-blockquote__footer">
          {name && <span className="eh-blockquote__name">{name}</span>}
          {cite && (
            <cite className="eh-blockquote__cite">
              {citeUrl ? <a className="eh-blockquote__cite-link" href={citeUrl}>{cite}</a> : cite}
            </cite>
          )}
        </figcaption>
      </div>
    </figure>
  );
}
