SplitButton
===

A component for displaying a button with a main action plus a secondary set of actions behind a menu toggle.

## Usage

```jsx
<SplitButton
	isPrimary
	mainLabel="Primary Button"
	menuLabel="Select an action"
	onClick={ () => alert( 'Primary Main Action clicked' ) }
	controls={ [
		{
			label: 'Up',
			onClick: () => alert( 'Primary Up clicked' ),
		},
		{
			label: 'Down',
			icon: <Gridicon icon="arrow-down" />,
			onClick: () => alert( 'Primary Down clicked' ),
		},
	] }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`isPrimary` | Boolean | `false` | Whether the button is styled as a primary button
`mainIcon` | ReactNode | `null` | Icon for the main button
`mainLabel` | String | `null` | Label for the main button
`onClick` | Function | `noop` | Function to activate when the the main button is clicked
`menuLabel` | String | `null` | Label to display for the menu of actions, used as a heading on the mobile popover and for accessible text
`controls` | Array | `null` | (required) An array of additional actions. Accepts additional icon, label, and onClick props
`className` | String | `null` | Additional CSS classes

### `controls` structure

Array of additional actions with properties:

- `icon`: One of type: string, element
- `label`: String - Label displayed for this button.
- `onClick`: Function - Click handler for this button.