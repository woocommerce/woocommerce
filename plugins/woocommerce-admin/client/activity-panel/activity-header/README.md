ActivityHeader
============

A component designed for use in the activity panel. It returns a title and can optionally also include a component like dropdown or Ellipsis menu.

## How to use:

```jsx
import ActivityHeader from 'layout/activity-panel/activity-header';

render: function() {
  return (
    <ActivityHeader
      title="Reviews"
    />
  );
}
```

## Props

* `title`: A title for this card (required).
* `className`: Additional class names.
* `menu`: A dropdown menu (EllipsisMenu) shown at the top-right of the card.
