Flag
============

Use the `Flag` component to display a country's flag.

## How to use:

```jsx
import { Flag } from 'components/flag';

render: function() {
  return (
    <Flag
		code="US"
		size={ 24 }
	/>
  );
}
```

## Props

* `code`: Two letter, three letter or three digit country code.
* `order`: An order can be passed instead of `code` and the code will automatically be pulled from the billing or shipping data.
* `width`: Flag image width.
* `height`: Flag image height.
* `round`: Default `true`. True to display a rounded flag.
* `className`: Additional CSS classes.