One collapsible row. Expands by animating `grid-template-rows` 0fr → 1fr over 0.4s, so no height measuring and no layout jump.

```jsx
<AccordionContainer>
  <Accordion title="Where will it be held?" openByDefault>…</Accordion>
  <Accordion title="When do tickets go on sale?">…</Accordion>
</AccordionContainer>
```

Always wrap a set in `AccordionContainer`. The chevron rotates 180°; the trigger is a real button with `aria-expanded`.
