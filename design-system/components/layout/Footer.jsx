import React from 'react';

export function Footer({ brand, actions, utilityStart, utilityEnd, horizontal = false }) {
  return (
    <section className={`eh-footer${horizontal ? ' eh-footer--horizontal' : ''}`}>
      <div className="eh-footer__inner">
        <div className="eh-footer__top">
          <div className="eh-footer__first">{brand}</div>
          <div className="eh-footer__last">{actions}</div>
        </div>
        <div className="eh-footer__bottom">
          <div className="eh-footer__utility-first">{utilityStart}</div>
          <div className="eh-footer__utility-last">{utilityEnd}</div>
        </div>
      </div>
    </section>
  );
}
