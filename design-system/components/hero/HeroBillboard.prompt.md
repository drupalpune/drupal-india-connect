Full-bleed image hero for inner pages and campaign landing pages.

```jsx
<HeroBillboard media="assets/venue.jpg" mediaAlt="" overlayOpacity="40%" height="ribbon">
  <Heading text="Venue & travel" level={1} size="7xl" color="inverted" />
</HeroBillboard>
```

Not for the event homepage — that uses `SiteHero25`. When text sits on an image, always raise `overlayOpacity` until the copy passes contrast; the overlay is white, so pair it with dark text.
