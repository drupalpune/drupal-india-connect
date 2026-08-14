import React from 'react';

export function Text({ children, html, size = 'normal', color = 'default' }) {
  const classes = ['eh-text', `eh-text--size-${size}`, `eh-text--color-${color}`].join(' ');
  if (html) return <div className={classes} dangerouslySetInnerHTML={{ __html: html }} />;
  return <div className={classes}>{children}</div>;
}
