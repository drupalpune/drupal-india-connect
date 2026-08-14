/* global window, React */
const { AnnouncementBar, Navbar, Footer, Button, Text } = window.DS;

const NAV = [
  { label: 'Home', key: 'home' },
  { label: 'Speakers', key: 'speakers' },
  { label: 'Schedule', key: 'schedule' },
  { label: 'Sponsors', key: 'sponsors' },
];

window.Screens = window.Screens || {};

window.Screens.Shell = function Shell({ current, onNavigate, children }) {
  return (
    <div style={{ background: 'var(--surface-page)' }}>
      <AnnouncementBar
        message="Save the date — 18–20 January 2027"
        detail="Host city and venue announcement coming soon"
        variant="secondary"
      />
      <Navbar
        logo={<a href="#home" onClick={(e) => { e.preventDefault(); onNavigate('home'); }}><img src="../../assets/logo.svg" alt="DrupalAsia Connect" /></a>}
        items={NAV.map((n) => ({
          label: n.label,
          href: '#' + n.key,
          active: current === n.key,
        }))}
        actions={<Button label="Get updates" size="small" href="#notify" />}
      />
      <div onClick={(e) => {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;
        const key = link.getAttribute('href').slice(1);
        if (NAV.some((n) => n.key === key)) { e.preventDefault(); onNavigate(key); }
      }}>
        {children}
      </div>
      <Footer
        brand={(
          <>
            <img src="../../assets/logo.svg" alt="DrupalAsia Connect" />
            <Text size="sm">A community-run event for the Drupal community of Asia. 18–20 January 2027.</Text>
          </>
        )}
        actions={<Button label="Become a sponsor" variant="secondary" size="small" href="#sponsors" />}
        utilityStart={(
          <ul className="menu" style={{ listStyle: 'none', margin: 0, padding: 0 }}>
            <li><a href="#faq">Code of conduct</a></li>
            <li><a href="#faq">Privacy</a></li>
            <li><a href="mailto:hello@drupalasia.org">hello@drupalasia.org</a></li>
          </ul>
        )}
        utilityEnd={<span>© 2026 DrupalAsia Connect. Drupal is a registered trademark of Dries Buytaert.</span>}
        horizontal
      />
    </div>
  );
};
