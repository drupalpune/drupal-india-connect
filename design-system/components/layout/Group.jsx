import React from 'react';

export function Group({
  children,
  direction = 'column',
  itemsAlign = 'start',
  selfAlign = 'center',
  gap,
  equalChildren = false,
  radius,
  padding,
  background,
  animation,
  animationDelay,
  animationDuration,
}) {
  const classes = [
    'eh-group',
    `eh-group--${direction}`,
    `eh-group--items-${itemsAlign}`,
    `eh-group--self-${selfAlign}`,
    gap ? `eh-group--gap-${gap}` : null,
    equalChildren ? 'eh-group--equal-children' : null,
    radius ? `eh-group--radius-${radius}` : null,
    padding ? `eh-group--padding-${padding}` : null,
    background ? `eh-group--bg-${background}` : null,
    animation ? `eh-group--anim-${animation}` : null,
  ].filter(Boolean).join(' ');

  const style = {};
  if (animationDelay) style['--eh-anim-delay'] = animationDelay;
  if (animationDuration) style['--eh-anim-duration'] = animationDuration;

  return <div className={classes} style={style}>{children}</div>;
}
