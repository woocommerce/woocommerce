Card
====

A basic card component with a header. The header can contain a title (required), an action (optional), and an `EllipsisMenu` menu (optional).

## How to use:

```jsx
import Card from 'components/card';

render: function() {
  return (
    <Card title={ "Store Performance" }>
      <p>Your stuff in a Card</p>
    </Card>
  );
}
```

## Props

* `title` (required): The title to use for this card.
* `action`: One "primary" action for this card, appears in the card header
* `className`: You can add classes to the card container.
* `menu`: An EllipsisMenu, with filters used to control the content visible in this card
