OrderStatus
============

Use `OrderStatus` to display a badge with human-friendly text describing the current order status.

## How to use:

```jsx
import OrderStatus from 'components/order-status';

render: function() {
  return (
	<OrderStatus
		order={ order }
	/>
  );
}
```

## Props

* `order` (required): The order to display a status for.
* `className`: Additional CSS classes.