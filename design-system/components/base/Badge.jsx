import React from 'react';
import { Icon } from './Icon.jsx';

export function Badge({ label, style = 'primary', icon, iconFirst = true, url }) {
  const classes = [
    'eh-badge',
    `eh-badge--${style}`,
    icon ? 'eh-badge--has-icon' : null,
    iconFirst ? 'eh-badge--icon-first' : 'eh-badge--icon-last',
  ].filter(Boolean).join(' ');

  const content = (
    <>
      {icon && <Icon icon={icon} size="extra-small" />}
      <span className="eh-badge__label">{label}</span>
    </>
  );

  if (url) return <a href={url} className={classes} aria-label={label}>{content}</a>;
  return <span className={classes} aria-label={label}>{content}</span>;
}
