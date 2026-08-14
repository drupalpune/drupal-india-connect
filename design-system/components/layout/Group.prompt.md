Flex wrapper for arranging things inside a Section cell — button rows, stacked text blocks, padded panels.

```jsx
<Group direction="row" gap="md" itemsAlign="center">
  <Button label="Get updates" />
  <Button label="Plan to speak" variant="secondary-inverted" />
</Group>
```

Group is for arrangement, Section is for page structure — don't use Group to build a page band. The four fade animations are 0.5s and honour `--eh-anim-delay`.
