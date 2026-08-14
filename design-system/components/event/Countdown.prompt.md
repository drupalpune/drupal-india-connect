Intentional addition (not in the base theme): the live countdown to day one — the only moving thing on the homepage.

```jsx
<Countdown target="2027-01-18T09:00:00+05:30" eyebrow="Countdown to day one" label="Monday 18 January 2027, 09:00" variant="dark" />
```

Days are unpadded, the other three are two digits, and the figures use tabular numerals so nothing jogs. The seconds cell gets the secondary accent rule; the rest get primary. `role="timer"` with `aria-live="off"` keeps screen readers quiet. In Drupal, feed `target` from the event term's `field_dates` rather than typing a date.
