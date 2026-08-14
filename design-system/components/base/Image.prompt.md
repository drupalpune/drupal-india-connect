Fixed-ratio, cover-cropped image with an optional caption.

```jsx
<Image src="assets/venue.jpg" alt="The main hall" ratio="4:3" radius="large" caption="Main hall, 2026" />
```

Always pick a ratio rather than letting intrinsic size decide — the grids depend on it. Speaker portraits are 1:1 and go through `FeaturedSpeaker`, which adds the duotone treatment.
