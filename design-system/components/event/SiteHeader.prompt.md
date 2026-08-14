The event site's own header — branding, primary menu and the black registration slab that bleeds off the right edge on wide screens.

```jsx
<SiteHeader branding={<a href="/"><img src="assets/logo.svg" alt="DrupalAsia Connect" /></a>} menu={<MainMenu />} registerLabel="Get tickets" registerHref="/tickets" />
```

This is the event-platform header, distinct from the generic `Navbar`. Pick one per site: `SiteHeader` for the campaign site, `Navbar` for simpler marketing pages.
