Semantic wrapper for a set of accordion rows. No styling of its own — the rows own their borders. In Drupal it also coordinates mutual-exclusivity between rows.

```jsx
<AccordionContainer>{faqs.map((f) => <Accordion key={f.q} title={f.q}>{f.a}</Accordion>)}</AccordionContainer>
```
