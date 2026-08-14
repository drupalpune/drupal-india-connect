import React from 'react';

export function Heading({
  text,
  level = 2,
  size,
  color = 'default',
  align = 'left',
  style: styleVariant,
  marginTop,
  url,
}) {
  const classes = [
    'eh-heading',
    size ? `eh-heading--size-${size}` : null,
    `eh-heading--color-${color}`,
    `eh-heading--align-${align}`,
    styleVariant && styleVariant !== 'default' ? `eh-heading--style-${styleVariant}` : null,
    marginTop ? `eh-heading--mt-${marginTop}` : null,
    url ? 'eh-heading--linked' : null,
  ].filter(Boolean).join(' ');

  const inner = url ? <a href={url}>{text}</a> : text;
  const Tag = level >= 1 && level <= 6 ? `h${level}` : 'span';
  return <Tag className={Tag === 'span' ? `${classes} eh-heading--span` : classes}>{inner}</Tag>;
}
