The general content card — news items, programme entries, anything with a title, a line of copy and maybe an image.

```jsx
<Card headingText="Save the date: 18–20 January 2027" text="The first pan-Asia Drupal event has its dates." url="/news/save-the-date" media="assets/news.jpg" />
```

Radius 12px. `framed` carries the system's only shadow; `full` is for edge-to-edge media. Passing `url` makes the entire card a click target (stretched `::after`) and adds a 1.02 hover scale — don't nest other links inside it.
