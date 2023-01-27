# Product Fields

Product Fields are used within the WooCommerce Admin product editor, for rendering new fields using PHP.

## Example

```js
// product-field.js
( function ( element ) {
	const el = element.createElement;

	registerProductField( 'number', {
		name: 'number',
		render: () => {
			return <InputControl type="number" />;
		},
	} );
} )( window.wp.element );
```

## API

### registerProductField

Registers a new product field provided a unique name and an object defining its
behavior.

_Usage_

```js
import { __ } from '@wordpress/i18n';
import { registerProductField } from '@woocommerce/components';

registerProductField( 'number', {
	name: 'number',
	render: () => {
		return <InputControl type="number" />;
	},
} );
```

_Parameters_

-   _fieldName_ `string`: Field name.
-   _settings_ `Object`: Field settings.
    -   _render_ `ComponentType`: React functional component to be rendered.
