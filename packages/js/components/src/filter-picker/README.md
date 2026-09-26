# Filter Picker

Modify a url query parameter via a dropdown selection of configurable options. This component manipulates the `filter` query parameter.

## Usage

```jsx
import { FilterPicker } from '@woocommerce/components';

const renderFilterPicker = () => {
	const config = {
		label: 'Meal',
		staticParams: [],
		param: 'meal',
		showFilters: function showFilters() {
			return true;
		},
		filters: [
			{ label: 'Breakfast', value: 'breakfast' },
			{
				label: 'Lunch',
				value: 'lunch',
				subFilters: [
					{ label: 'Meat', value: 'meat', path: [ 'lunch' ] },
					{ label: 'Vegan', value: 'vegan', path: [ 'lunch' ] },
					{ 
						label: 'Pescatarian',
						value: 'fish',
						path: [ 'lunch' ],
						subFilters: [
							{ label: 'Snapper', value: 'snapper', path: [ 'lunch', 'fish' ] },
							{ label: 'Cod', value: 'cod', path: [ 'lunch', 'fish' ] },
							// Specify a custom component to render (Work in Progress)
							{
								label: 'Other',
								value: 'other_fish',
								path: [ 'lunch', 'fish' ],
								component: 'OtherFish'
							},
						],
					},
				],
			},
			{ label: 'Dinner', value: 'dinner' },
		],
	};

	return <FilterPicker config={ config } path={ path } query={ query } />;
};
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`config` | Object | `null` | (required) An array of filters and subFilters to construct the menu
`path` | String | `null` | (required) The `path` parameter supplied by React-Router
`query` | Object | `{}` | The query string represented in object form
`onFilterSelect` | Function | `() => {}` | Function to be called after filter selection

### `config` structure

The `config` prop has the following structure:

- `label`: String - A label above the filter selector.
- `staticParams`: Array - Url parameters to persist when selecting a new filter.
- `param`: String - The url parameter this filter will modify.
- `defaultValue`: String - The default parameter value to use instead of 'all'.
- `showFilters`: Function - Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
- `filters`: Array - Array of filter objects.

### `filters` structure

The `filters` prop is an array of filter objects. Each filter object should have the following format:

- `chartMode`: One of: 'item-comparison', 'time-comparison'
- `component`: String - A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
- `label`: String - The label for this filter. Optional only for custom component filters.
- `path`: String - An array representing the "path" to this filter, if nested.
- `settings`: Object - Settings for a filter with a `component`, or for a comparison filter. See the `settings` structure below.
- `subFilters`: Array - An array of more filter objects that act as "children" to this item. This set of filters is shown if the parent filter is clicked.
- `value`: String - The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.

### `settings` structure

Two kinds of filter use `settings`. A filter with `component: 'Search'` renders a `Search` component in the dropdown, and `FilterPicker` reads the keys below. A comparison filter (a filter whose `value` starts with `compare`) renders a card instead, and the `Filters` component forwards its `settings` to [CompareFilter](../compare-filter/README.md) as props (`getLabels`, `param`, `labels.title`, `labels.update`, `labels.helpText`, `searchProps`).

A `component: 'Search'` filter has the following `settings` format:

- `param`: String - The url parameter the selected value is stored in.
- `getLabels`: Function - Function used to fetch labels for the selected values, returns a Promise.
- `labels.button`: String - Label shown in the dropdown button next to the selected value.
- `searchProps`: Object - Props forwarded to the `Search` component, except `selected`, `onChange`, `inlineTags`, and `staticResults`. `searchProps.type` is required. See [Search](../search/README.md) for the full list.

```jsx
{
	component: 'Search',
	value: 'single_product',
	path: [ 'select_product' ],
	settings: {
		param: 'products',
		getLabels: getProductLabels,
		labels: {
			button: 'Single product',
		},
		searchProps: {
			type: 'products',
			placeholder: 'Type to search for a product',
		},
	},
}
```

The `type`, `autocompleter`, and `labels.placeholder` settings are deprecated. Use `searchProps.type`, `searchProps.autocompleter`, and `searchProps.placeholder` instead. When a deprecated setting is set, it takes precedence over the matching `searchProps` value, so extensions that change it on core filters keep working. The deprecated settings are scheduled for removal in `@woocommerce/components` 15.0.0.
