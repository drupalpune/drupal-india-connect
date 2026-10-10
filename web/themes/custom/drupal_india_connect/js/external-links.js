/**
 * @file
 * Opens off-site links in a new tab, site-wide.
 *
 * Content cannot set this itself: the Canvas text formats strip `target`
 * and `rel` from rich text, and event_horizon's `button` has no target
 * prop. So any http(s) link to another host gets `target="_blank"` and
 * `rel="noopener"` here. Links that already set a target are left alone.
 */
((Drupal, once) => {
  const isExternal = (link) => {
    if (link.protocol !== 'http:' && link.protocol !== 'https:') {
      return false;
    }
    return link.hostname !== window.location.hostname;
  };

  Drupal.behaviors.dacExternalLinks = {
    attach(context) {
      once('dac-external-links', 'a[href]', context).forEach((link) => {
        if (link.hasAttribute('target') || !isExternal(link)) {
          return;
        }
        link.setAttribute('target', '_blank');
        const rel = new Set((link.getAttribute('rel') || '').split(/\s+/).filter(Boolean));
        rel.add('noopener');
        link.setAttribute('rel', [...rel].join(' '));
      });
    },
  };
})(Drupal, once);
