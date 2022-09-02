DropdownButton
===

A button useful for a launcher of a dropdown component. The button is 100% width of its container and displays single or multiple lines rendered as `<span/>` elments.

## Usage

```jsx
<Dropdown
	renderToggle={ ( { isOpen, onToggle } ) => (
		<DropdownButton
			onClick={ onToggle }
			isOpen={ isOpen }
			labels={ [ 'All products Sold' ] }
		/>
	) }
	renderContent={ () => (
		<p>Dropdown content here</p>
	) }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`labels` | Array | `null` | (required) An array of elements to be rendered as the content of the button
`isOpen` | Boolean | `null` | Boolean describing if the dropdown in open or not
