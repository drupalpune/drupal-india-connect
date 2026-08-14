import React from 'react';

export function AnnouncementBar({ message, detail, linkUrl, linkText, variant = 'secondary' }) {
  if (!message) return null;
  return (
    <div className={`eh-announcement-bar eh-announcement-bar--${variant}`} role="region" aria-label="Event announcement">
      <div className="eh-announcement-bar__inner container">
        <span className="eh-announcement-bar__message">{message}</span>
        {detail && (
          <>
            <span className="eh-announcement-bar__separator" aria-hidden="true">•</span>
            <span className="eh-announcement-bar__detail">{detail}</span>
          </>
        )}
        {linkUrl && linkText && <a className="eh-announcement-bar__link" href={linkUrl}>{linkText}</a>}
      </div>
    </div>
  );
}
