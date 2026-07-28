# Cart Item Actions Registry

A registry system for adding custom action components to cart line items in WooCommerce Blocks.

## Overview

The Cart Item Actions registry allows extensions to register custom components that will be rendered for each cart line item. This is useful for adding functionality like editing prices, removing items with custom logic, or displaying item-specific actions.

## How It Works

- **Registry Pattern**: Components are registered globally using `registerCartItemAction()`
- **Per-Item Rendering**: Each registered component is rendered individually for every cart item
- **Props Passing**: Components receive `lineItem` and `cart` props for context

## API

### `registerCartItemAction(component)`

Register a component to be rendered for each cart line item.

**Parameters:**
- `component` (Function): A React component that receives `{ lineItem, cart }` props

**Returns:** void

### `getCartItemActions()`

Get all registered cart item action components.

**Returns:** Array of registered components

## Usage Example

```javascript
import { registerCartItemAction } from '@woocommerce/blocks-checkout';
import { __ } from '@wordpress/i18n';

const MyCartItemAction = ( { lineItem, cart } ) => {
	// Only show for specific products
	if ( ! lineItem.extensions?.my_plugin?.show_action ) {
		return null;
	}

	const handleClick = () => {
		console.log( 'Action clicked for item:', lineItem.key );
	};

	return (
		<button
			className="my-custom-action"
			onClick={ handleClick }
		>
			{ __( 'Custom Action', 'my-plugin' ) }
		</button>
	);
};

registerCartItemAction( MyCartItemAction );
```

## Component Props

### `lineItem`

The cart item object containing:
- `key` - Unique identifier for the cart item
- `name` - Product name
- `quantity` - Item quantity
- `prices` - Price data including raw_prices, currency info
- `extensions` - Extension data for the item
- And more...

### `cart`

The full cart object containing:
- `items` - Array of all cart items
- `totals` - Cart totals
- `coupons` - Applied coupons
- And more...

## Conditional Rendering

Components should return `null` when they don't want to render for a specific cart item:

```javascript
const MyAction = ( { lineItem } ) => {
	// Don't render for certain product types
	if ( lineItem.type !== 'simple' ) {
		return null;
	}

	return <button>My Action</button>;
};
```

## Best Practices

1. **Conditional Rendering**: Always check if your action should display for the current item
2. **Null Returns**: Return `null` when your action shouldn't render
3. **Extension Data**: Use `lineItem.extensions.your_namespace` to pass custom data
4. **Styling**: Use WooCommerce block component classes for consistency
5. **Accessibility**: Follow accessibility best practices (keyboard support, ARIA labels)

## Integration with Store API

To pass custom data to your cart item actions, extend the Store API cart item schema:

```php
woocommerce_store_api_register_endpoint_data(
	array(
		'endpoint'        => CartItemSchema::IDENTIFIER,
		'namespace'       => 'my_plugin',
		'data_callback'   => function( $cart_item ) {
			return array(
				'show_action' => true,
				'custom_data' => 'value',
			);
		},
		'schema_callback' => function() {
			return array(
				'show_action' => array(
					'type'     => 'boolean',
					'readonly' => true,
				),
			);
		},
	)
);
```
