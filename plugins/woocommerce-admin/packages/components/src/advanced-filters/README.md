Advanced Filters
============

Displays a configurable set of filters which can modify query parameters. Display, behavior, and types of filters can be designated by a configuration object.

## Example Config

Below is a config example complete with translation strings. Advanced Filters makes use of [interpolateComponents](https://github.com/Automattic/interpolate-components#readme) to organize sentence structure, resulting in a filter visually represented as a sentence fragment in any language.

```jsx
const config = {
	title: _x(
		'Orders Match {{select /}} Filters',
		'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
		'woocommerce-admin'
	),
	filters: {
		status: {
			labels: {
				add: __( 'Order Status', 'woocommerce-admin' ),
				remove: __( 'Remove order status filter', 'woocommerce-admin' ),
				rule: __( 'Select an order status filter match', 'woocommerce-admin' ),
				/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( 'Order Status {{rule /}} {{filter /}}', 'woocommerce-admin' ),
				filter: __( 'Select an order status', 'woocommerce-admin' ),
			},
			rules: [
				{
					value: 'is',
					/* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Is', 'order status', 'woocommerce-admin' ),
				},
				{
					value: 'is_not',
					/* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Is Not', 'order status', 'woocommerce-admin' ),
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( orderStatuses ).map( key => ( {
					value: key,
					label: orderStatuses[ key ],
				} ) ),
			},
		},
	},
  };
```

## Input Components

### SelectControl

Render a select component with options.

```jsx
input: {
	component: 'SelectControl',
	options: [
		{ label: 'Apples', key: 'apples' },
		{ label: 'Oranges', key: 'oranges' },
		{ label: 'Bananas', key: 'bananas' },
		{ label: 'Cherries', key: 'cherries' },
	],
}
```

`options`: An array of objects with `key` and `label` properties.

### Search

Render an input for users to search and select using an autocomplete.

```jsx
input: {
	component: 'Search',
	type: 'products',
	getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
		id: product.id,
		label: product.name,
	} ) ),
}
```

`type`: A string Autocompleter type used by the [Search Component](https://github.com/woocommerce/woocommerce-admin/tree/master/packages/components/src/search).
`getLabels`: A function returning a Promise resolving to an array of objects with `id` and `label` properties.

### Date
under development.

### Date Range
under development.

### Numerical Value
under development.

### Numerical Range
under development.

## AdvancedFilters Props

* `config` (required): The configuration object required to render filters
* `path` (required): The `path` parameter supplied by React-Router
* `query`: The url query string represented in object form
