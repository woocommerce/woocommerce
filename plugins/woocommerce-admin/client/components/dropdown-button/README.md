Dropdown Button
====

A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays single or multiple lines rendered as `<span/>` elments.

## How to use:

```jsx
import { Dropdown } from '@wordpress/components';
import DropdownButton from 'components/dropdown-button';

render: function() {
  return (
    <Dropdown
		renderToggle={ ( { isOpen, onToggle } ) => (
			<DropdownButton
				onClick={ onToggle }
				isOpen={ isOpen }
				labels={ [ 'All Products Sold' ] }
			/>
		) }
		renderContent={ () => (
			<p>Dropdown content here</p>
		) }
	/>
  );
}
```

## Props

* `labels`: An array of elements to be rendered as the content of the button
* `isOpen`: boolean describing if the dropdown in open or not
* `*`: All other props are passed to Gutenberg's `<Button />` component
