THE event homepage hero, and the loudest thing in the system: the event name set in gigantic uppercase type with a scroll-driven gradient fill, a 1:1 video well with a secondary→primary gradient wash, and the rotated date/location tab.

```jsx
<SiteHero25
  eventName="DrupalAsia Connect 2027"
  description="Three days of sessions, workshops and contribution sprints."
  ctaLabel="Watch the teaser"
  ctaHref="#teaser"
  dates="18–20 January 2027"
  location="Asia — city TBA"
  videoSrc="assets/teaser.mp4"
  videoType="video/mp4"
  posterSrc="assets/teaser.webp"
  textureSrc="assets/textures/trees.webp"
/>
```

Use once, at the top of the homepage, and never with another hero on the same page. In Drupal its content comes from the Event Details config page, not from props. The title fill animates on scroll via `animation-timeline: view()` and is disabled under `prefers-reduced-motion`.
