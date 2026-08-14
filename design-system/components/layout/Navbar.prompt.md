Site navigation. Inline row above 1100px; below that it collapses into a hamburger-triggered dropdown panel.

```jsx
<Navbar
  logo={<a href="/"><img src="assets/logo.svg" alt="DrupalAsia Connect" /></a>}
  items={[{ label: 'Speakers', href: '/speakers' }, { label: 'Schedule', href: '/schedule', active: true }]}
  actions={<Button label="Get tickets" size="small" />}
/>
```

The breakpoint is 1100px (`--wide-nav`), not the usual 1000px — the menu plus CTA needs the extra room. Don't add a second hamburger; this component and `SiteHeader` each already have one.
