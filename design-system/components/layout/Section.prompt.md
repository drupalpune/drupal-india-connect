The page's structural unit. Owns the 1400px max width, the responsive column grid, band background and vertical spacing. Nearly every screen is a stack of Sections.

```jsx
<Section columns="50-50" backgroundColor="accent" fullWidth paddingBlockStart="64" paddingBlockEnd="64"
  header={<Heading text="Why attend" level={2} size="5xl" />}>
  <Text>…</Text>
  <Text>…</Text>
</Section>
```

Rules: at most two band colours per page. Reach for `accent` (pale sky) for light bands and `black` + `darkBackground` for dark ones; `primary`/`secondary` bands are for single high-emphasis moments like the newsletter. Always pair a background colour with `fullWidth` unless you specifically want an inset panel.
