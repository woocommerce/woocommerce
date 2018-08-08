Advanced Filters
============

Displays a configurable set of filters which can modify query parameters.

## How to use:

```jsx
import AdvancedFilters from 'components/advanced-filters';

filters = {
	status: {
		label: __( 'Order Status', 'wc-admin' ),
		addLabel: __( 'Order Status', 'wc-admin' ),
		rules: [
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is-not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'SelectControl',
			options: [
				{ value: 'pending', label: __( 'Pending', 'wc-admin' ) },
				{ value: 'processing', label: __( 'Processing', 'wc-admin' ) },
				{ value: 'on-hold', label: __( 'On Hold', 'wc-admin' ) },
			],
		},
	},
	product: {
		label: __( 'Product', 'wc-admin' ),
		addLabel: __( 'Products', 'wc-admin' ),
		rules: [
			{ value: 'includes', label: __( 'Includes', 'wc-admin' ) },
			{ value: 'excludes', label: __( 'Excludes', 'wc-admin' ) },
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is-not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'FormTokenField',
		},
	},
};

render: function() {
  return (
    <AdvancedFilters config={ filters } />
  );
}
```

## AdvancedFilters Props

* `config` (required): The configuration object required to render filters

## config object jsDoc

```js
/**
 * @type filterConfig {{
 * 	key: {
 * 		label: {string},
 * 		addLabel: {string},
 * 		rules: [{{ value:{string}, label:{string} }}],
 * 		input: {
 * 			component: {string},
 * 			[options]: [*]
 * 		},
 * 	}
 * }}
 */
```
