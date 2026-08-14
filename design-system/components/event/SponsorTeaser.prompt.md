Sponsor logo tile for the sponsor listing. Grayscale until hover, one asymmetric rounded corner (top-right), and a primary-coloured bar that wipes in from the left on hover.

```jsx
<SponsorTeaser name="Acme" logo="assets/sponsors/acme.svg" href="https://acme.example" />
```

Tier treatment is contextual, not a prop: the top tier gets a sky-tinted background and reveals its blurb. Use `CardLogo` only for decorative logo walls that aren't the real sponsor listing.
