SplitButton
============

A component for displaying a button with a main action plus a secondary set of actions behind a menu toggle.

## How to use:

```jsx
import SplitButton from 'components/split-button';

render: function() {
  return (
	<div>
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
					label: 'Right',
					onClick: () => alert( 'Primary Right clicked' ),
				},
				{
					label: 'Down',
					icon: <Gridicon icon="arrow-down" />,
					onClick: () => alert( 'Primary Down clicked' ),
				},
				{
					label: 'Left',
					icon: <Gridicon icon="arrow-left" />,
					onClick: () => alert( 'Primary Left clicked' ),
				},
			] }
				/>
		<SplitButton
			mainIcon={ <Gridicon icon="pencil" /> }
			menuLabel="Select an action"
			onClick={ () => alert( 'Icon Only Action clicked' ) }
			controls={ [
				{
					label: 'Up',
					icon: <Gridicon icon="arrow-up" />,
					onClick: () => alert( 'Icon Only Up clicked' ),
				},
				{
					label: 'Right',
					onClick: () => alert( 'Icon Only Right clicked' ),
				},
				{
					label: 'Down',
					icon: <Gridicon icon="arrow-down" />,
					onClick: () => alert( 'Icon Only Down clicked' ),
				},
				{
					icon: <Gridicon icon="arrow-left" />,
					onClick: () => alert( 'Icon Only Left clicked' ),
				},
			] }
		/>
	</div>
  );
}
```
## Props

* `isPrimary`: Default false. Whether the button is styled as a primary button.
* `mainIcon`: Icon for the main button.
* `mainLabel`: Label for the main button.
* `onClick`: Function to activate when the the main button is clicked.
* `menuLabel`: Label to display for the menu of actions, used as a heading on the mobile popover and for accessible text.
* `controls`: An array of additional actions. Accepts additional icon, label, and onClick props.
* `className`: Additional CSS classes.