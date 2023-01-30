Filter Picker
===

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
- `param`: String - The url paramter this filter will modify.
- `defaultValue`: String - The default paramter value to use instead of 'all'.
- `showFilters`: Function - Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
- `filters`: Array - Array of filter objects.

### `filters` structure

The `filters` prop is an array of filter objects. Each filter object should have the following format:

- `chartMode`: One of: 'item-comparison', 'time-comparison'
- `component`: String - A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
- `label`: String - The label for this filter. Optional only for custom component filters.
- `path`: String - An array representing the "path" to this filter, if nested.
- `subFilters`: Array - An array of more filter objects that act as "children" to this item. This set of filters is shown if the parent filter is clicked.
- `value`: String - The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
