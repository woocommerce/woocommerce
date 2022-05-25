EllipsisMenu
===

This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.

## Usage

```jsx
<EllipsisMenu label="Choose which analytics to display"
	renderContent={ ( { onToggle } )=> {
		return (
			<div>
				<MenuTitle>Display stats</MenuTitle>
				<MenuItem onInvoke={ () => setState( { showCustomers: ! showCustomers } ) }>
					<ToggleControl
						label="Show Customers"
						checked={ showCustomers }
						onChange={ () => setState( { showCustomers: ! showCustomers } ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ () => setState( { showOrders: ! showOrders } ) }>
					<ToggleControl
						label="Show Orders"
						checked={ showOrders }
						onChange={ () => setState( { showOrders: ! showOrders } ) }
					/>
				</MenuItem>
				<MenuItem onInvoke={ onToggle }>
					<Button
						label="Close menu"
						onClick={ onToggle }
					>
					Close Menu
					</Button>
				</MenuItem>
			</div>
		);
	} }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`label` | String | `null` | (required) The label shown when hovering/focusing on the icon button
`renderContent` | Function | `null` | A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments


MenuItem
===

`MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`).
`MenuItem`s can also be deemed "clickable", though this is disabled by default because generally the inner component handles
the click event.

## Usage

```jsx
<MenuItem onInvoke={ onToggle }>
	<Button
		label="Close menu"
		onClick={ onToggle }
	>
	Close Menu
	</Button>
</MenuItem>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`checked` | Boolean | `null` | Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`
`children` | ReactNode | `null` | A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`
`isCheckbox` | Boolean | `false` | Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role)
`isClickable` | Boolean | `false` | Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component handles the click event
`onInvoke` | Function | `null` | (required) A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked (only if `isClickable` is set)


MenuTitle
===

`MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
(so this should not be used in place of the `EllipsisMenu` prop `label`).

## Usage

```jsx
<MenuTitle>Display stats</MenuTitle>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | ReactNode | `null` | A renderable component (or string) which will be displayed as the content of this item
