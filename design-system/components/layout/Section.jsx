import React from 'react';

export function Section({
  children,
  header,
  footer,
  columns = '100',
  mobileColumns,
  gridAlign,
  backgroundColor,
  backgroundImage,
  darkBackground = false,
  fullWidth = false,
  width = '100%',
  marginBlockStart = '0',
  marginBlockEnd = '0',
  paddingBlockStart = '0',
  paddingBlockEnd = '0',
}) {
  const isDark = darkBackground || backgroundColor === 'black';
  const classes = [
    'eh-section',
    `eh-section--width-${String(width).replace('%', '')}`,
    `eh-section--mt-${marginBlockStart}`,
    `eh-section--mb-${marginBlockEnd}`,
    `eh-section--pt-${paddingBlockStart}`,
    `eh-section--pb-${paddingBlockEnd}`,
    backgroundColor ? `eh-section--bg-${backgroundColor}` : null,
    isDark ? 'eh-section--dark-background' : null,
    backgroundColor && fullWidth ? 'eh-section--full-width' : null,
  ].filter(Boolean).join(' ');

  const gridClasses = [
    'eh-section__grid',
    `eh-section__grid--cols-${columns}`,
    mobileColumns && mobileColumns !== '1' ? `eh-section__grid--mobile-${mobileColumns}` : null,
    gridAlign && gridAlign !== 'stretch' ? `eh-section__grid--align-${gridAlign}` : null,
  ].filter(Boolean).join(' ');

  return (
    <section className={classes}>
      {backgroundImage && (
        <div className="eh-section__bg-media">
          <img className="eh-section__bg-image" src={backgroundImage} alt="" />
        </div>
      )}
      {backgroundColor && <div className={`eh-section__bg-overlay eh-section__bg-overlay--${backgroundColor}`} />}
      <div className="eh-section__inner">
        {header && <header className="eh-section__header">{header}</header>}
        <div className={gridClasses}>{children}</div>
        {footer && <footer className="eh-section__footer">{footer}</footer>}
      </div>
    </section>
  );
}
