import React from 'react';
import { Icon } from './Icon.jsx';

export function Button({
  label,
  href,
  variant = 'primary',
  size = 'medium',
  icon,
  iconFirst = false,
  fullWidthMobile = false,
  disabled = false,
  onClick,
}) {
  const classes = [
    'eh-button',
    `eh-button--${variant}`,
    `eh-button--${size}`,
    icon ? 'eh-button--has-icon' : null,
    icon && iconFirst ? 'eh-button--icon-first' : null,
    fullWidthMobile ? 'eh-button--full-width' : null,
    disabled ? 'eh-button--disabled' : null,
  ].filter(Boolean).join(' ');

  const glyph = icon ? <span className="eh-button__icon"><Icon icon={icon} size="extra-small" /></span> : null;
  const content = iconFirst ? <>{glyph}{label}</> : <>{label}{glyph}</>;

  if (href && !disabled) {
    return <a href={href} className={classes} onClick={onClick}>{content}</a>;
  }
  return (
    <button type="button" className={classes} disabled={disabled} aria-disabled={disabled || undefined} onClick={onClick}>
      {content}
    </button>
  );
}
