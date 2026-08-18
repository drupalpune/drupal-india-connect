A sponsorship package: tier name, price, a benefits checklist, and an optional call to action. Not a ticket-pricing component — `card-pricing` stays excluded upstream because ticketing belongs to commerce; this is informational only.

```jsx
<SponsorTier tierName="Presenting" price="₹550,000" tierAccent="secondary" benefits="<ul><li>12 event tickets + early bird pricing on 6 additional tickets</li><li>6m x 3m expo hall space with 1st booth selection priority</li></ul>" ctaText="Become a sponsor" ctaUrl="/sponsor-us" />
```

`tierAccent` colours the top border and tier name from tokens already in the system — `secondary` (the brand orange, spend it on at most one tier per page), `primary-50`, `primary-60`, or `neutral`. For a four-tier row: secondary on the top tier, then step down through primary-50 → primary-60 → neutral so darker reads as higher tier. `benefits` is edited as a rich-text bulleted list in Canvas, same shape as `PeopleCard`'s description — each `<li>` gets a check mark automatically, no per-item markup needed. Leave `ctaUrl` empty and the card just ends after the last benefit.
