# Advanced Filters

Displays a configurable set of filters which can modify query parameters. Display, behavior, and types of filters can be designated by a configuration object.

## Usage

Below is a config example complete with translation strings. Advanced filters makes use of [interpolateComponents](https://github.com/Automattic/interpolate-components#readme) to organize sentence structure, resulting in a filter visually represented as a sentence fragment in any language.

```js
const config = {
	title: __(
		// A sentence describing filters for Orders
		// See screen shot for context: https://cloudup.com/cSsUY9VeCVJ
		'Orders Match {{select /}} Filters',
		'woocommerce'
	),
	filters: {
		status: {
			labels: {
				add: __( 'Order Status', 'woocommerce' ),
				remove: __( 'Remove order status filter', 'woocommerce' ),
				rule: __(
					'Select an order status filter match',
					'woocommerce'
				),
				// A sentence describing an Order Status filter
				// See screen shot for context: https://cloudup.com/cSsUY9VeCVJ
				title: __(
					'Order Status {{rule /}} {{filter /}}',
					'woocommerce'
				),
				filter: __( 'Select an order status', 'woocommerce' ),
			},
			rules: [
				{
					value: 'is',
					// Sentence fragment, logical, "Is"
					// Refers to searching for orders matching a chosen order status
					// Screenshot for context: https://cloudup.com/cSsUY9VeCVJ
					label: _x( 'Is', 'order status', 'woocommerce' ),
				},
				{
					value: 'is_not',
					// Sentence fragment, logical, "Is Not"
					// Refers to searching for orders that don't match a chosen order status
					// Screenshot for context: https://cloudup.com/cSsUY9VeCVJ
					label: _x( 'Is Not', 'order status', 'woocommerce' ),
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( orderStatuses ).map( ( key ) => ( {
					value: key,
					label: orderStatuses[ key ],
				} ) ),
			},
			allowMultiple: false, // Set to true to allow multiple instances of this filter.
		},
	},
};
```

When filters are applied, the query string will be modified using a combination of rule names and selected filter values.

Taking the above configuration as an example, applying the filter will result in a query parameter like `status_is=pending` or `status_is_not=cancelled`.

### Props

| Name                     | Type     | Default   | Description                                                                        |
| ------------------------ | -------- | --------- | ---------------------------------------------------------------------------------- |
| `config`                 | Object   | `null`    | (required) The configuration object required to render filters. See example above. |
| `path`                   | String   | `null`    | (required) Name of this filter, used in translations.                              |
| `query`                  | Object   | `null`    | The query string represented in object form.                                       |
| `onAdvancedFilterAction` | Function | `null`    | Function to be called after an advanced filter action has been taken.              |
| `siteLocale`             | string   | `'en_US'` | The siteLocale for the site.                                                       |
| `currency`               | Object   | `null`    | (required) The currency instance for the site (@woocommerce/currency).             |

## Input Components

### SelectControl

Render a select component with options.

```js
const config = {
	...,
	filters: {
		fruit: {
			input: {
				component: 'SelectControl',
				options: [
					{ label: 'Apples', key: 'apples' },
					{ label: 'Oranges', key: 'oranges' },
					{ label: 'Bananas', key: 'bananas' },
					{ label: 'Cherries', key: 'cherries' },
				],
			},
		},
	},
};
```

`options`: An array of objects with `key` and `label` properties.

### Search

Render an input for users to search and select using an autocomplete.

```js
const config = {
	...,
	filters: {
		product: {
			input: {
				component: 'Search',
				type: 'products',
				getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
					id: product.id,
					label: product.name,
				} ) ),
			},
		},
	},
};
```

`type`: A string Autocompleter type used by the [Search Component](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/components/src/search).
`getLabels`: A function returning a Promise resolving to an array of objects with `id` and `label` properties.

### Date

Renders an input or two inputs allowing a user to filter based on a date value or range of values.

```js
const config = {
	...,
	filters: {
		registered: {
			rules: [
				{
					value: 'before',
					label: __( 'Before', 'woocommerce' ),
				},
				{
					value: 'after',
					label: __( 'After', 'woocommerce' ),
				},
				{
					value: 'between',
					label: __( 'Between', 'woocommerce' ),
				},
			],
			input: {
				component: 'Date',
			},
		},
	},
};
```

### Numeric Value

Renders an input or two inputs allowing a user to filter based on a numeric value or range of values. Can also render inputs for currency values.

Valid rule values are `after`, `before`, and `between`. Use any combination you'd like.

```js
const config = {
	...,
	filters: {
		quantity: {
			rules: [
				{
					value: 'lessthan',
					label: __( 'Less Than', 'woocommerce' ),
				},
				{
					value: 'morethan',
					label: __( 'More Than', 'woocommerce' ),
				},
				{
					value: 'between',
					label: __( 'Between', 'woocommerce' ),
				},
			],
			input: {
				component: 'Number',
			},
		},
	},
};
```

Valid rule values are `lessthan`, `morethan`, and `between`. Use any combination you'd like.

Specify `input.type` as `'currency'` if you'd like to render currency inputs, which respects store currency locale.
