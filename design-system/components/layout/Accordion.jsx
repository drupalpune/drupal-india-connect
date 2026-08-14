import React from 'react';

export function Accordion({ title, children, headingLevel = 3, openByDefault = false }) {
  const [open, setOpen] = React.useState(openByDefault);
  const id = React.useId();
  const triggerId = `eh-accordion-trigger-${id}`;
  const contentId = `eh-accordion-content-${id}`;
  const Tag = `h${headingLevel}`;

  return (
    <div className={`eh-accordion${open ? ' eh-accordion--open' : ''}`} data-open-by-default={openByDefault ? 'true' : 'false'}>
      <Tag className="eh-accordion__heading">
        <button
          id={triggerId}
          className="eh-accordion__trigger"
          aria-controls={contentId}
          aria-expanded={open}
          onClick={() => setOpen((v) => !v)}
        >
          <span className="eh-accordion__trigger-text">{title}</span>
          <span className="eh-accordion__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor" focusable="false">
              <path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z" />
            </svg>
          </span>
        </button>
      </Tag>
      <div id={contentId} className="eh-accordion__content" role="region" aria-labelledby={triggerId}>
        <div className="eh-accordion__content-inner">{children}</div>
      </div>
    </div>
  );
}
