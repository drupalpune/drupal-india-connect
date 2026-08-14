import React from 'react';

export function Navbar({ logo, items = [], actions, menuAlign = 'center' }) {
  const [open, setOpen] = React.useState(false);
  const navId = 'eh-navbar-menu';

  return (
    <nav className="eh-navbar">
      <div className="eh-navbar__bar">
        <div className="eh-navbar__logo">{logo}</div>

        <div className={`eh-navbar__collapse${open ? ' eh-navbar__collapse--open' : ''}`} id={navId} aria-hidden={!open}>
          <div className={`eh-navbar__nav eh-navbar__nav--${menuAlign}`}>
            <ul className="menu">
              {items.map((item) => (
                <li className="menu__item" key={item.href + item.label}>
                  <a className={`menu__link${item.active ? ' is-active' : ''}`} href={item.href}>{item.label}</a>
                </li>
              ))}
            </ul>
          </div>
          <div className="eh-navbar__links">{actions}</div>
        </div>

        <button
          className="eh-navbar__hamburger"
          aria-label={open ? 'Hide menu' : 'Show menu'}
          aria-expanded={open}
          aria-controls={navId}
          onClick={() => setOpen((v) => !v)}
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256" aria-hidden="true" focusable="false">
            <rect x="40" y="128" width="176" height="16" rx="8" />
            <rect x="40" y="64" width="176" height="16" rx="8" />
            <rect x="40" y="192" width="176" height="16" rx="8" />
          </svg>
          <svg className="eh-navbar__close-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 256 256" aria-hidden="true" focusable="false">
            <line x1="200" y1="56" x2="56" y2="200" stroke="currentColor" strokeWidth="16" strokeLinecap="round" />
            <line x1="56" y1="56" x2="200" y2="200" stroke="currentColor" strokeWidth="16" strokeLinecap="round" />
          </svg>
        </button>
      </div>
    </nav>
  );
}
