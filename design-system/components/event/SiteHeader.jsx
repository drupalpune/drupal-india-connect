import React from 'react';

export function SiteHeader({ branding, menu, registerLabel, registerHref }) {
  return (
    <header className="site-header container">
      <div className="site-branding">
        <div className="site-branding__inner">{branding}</div>
      </div>
      {menu}
      <div className="site-header__action">
        {registerLabel && (
          <div className="site-header__register">
            <a className="site-header__register-link" href={registerHref}>
              {registerLabel}
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 256 256" aria-hidden="true" focusable="false">
                <path d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z" />
              </svg>
            </a>
          </div>
        )}
        <button className="mobile-nav-button" data-drupal-selector="mobile-nav-button" aria-label="Toggle Main Menu" aria-controls="header-nav" aria-expanded="false">
          <span className="mobile-nav-button__label">Menu</span>
          <span className="mobile-nav-button__icon" />
        </button>
      </div>
    </header>
  );
}
