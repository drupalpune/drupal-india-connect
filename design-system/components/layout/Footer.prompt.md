Site footer: a top row (branding + CTA) over a utility bar (links + copyright), separated by hairlines.

```jsx
<Footer
  brand={<><img src="assets/logo.svg" alt="DrupalAsia Connect" /><Text size="sm">18–20 January 2027</Text></>}
  actions={<Button label="Get tickets" />}
  utilityStart={<ul className="menu"><li><a href="/code-of-conduct">Code of conduct</a></li></ul>}
  utilityEnd={<span>© 2026 DrupalAsia Connect</span>}
  horizontal
/>
```

Utility text steps 12 → 14 → 16px across breakpoints. Keep the utility bar to links and legal lines only.
