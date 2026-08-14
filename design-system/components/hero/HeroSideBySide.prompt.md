Image beside copy — the venue block, a speaker spotlight, a sponsorship pitch. Row above 700px, stacked below.

```jsx
<HeroSideBySide media="assets/venue.jpg" mediaAlt="The main hall" imagePosition="right" imageSize="4:3" imageRadius="large">
  <Heading text="Host city announcement soon" level={2} size="5xl" />
  <Text>We are finalising the host city with regional organisers.</Text>
</HeroSideBySide>
```

On desktop the media panel stretches to the content's height, so the ratio prop only governs mobile.
