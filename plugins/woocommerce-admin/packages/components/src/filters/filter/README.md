Filter Picker
=============

Modify a url query parameter via a dropdown selection of configurable options. This component manipulates the `filter` query parameter.

## Usage

```jsx
import { FilterPicker } from '@woocommerce/components';

const renderFilterPicker = () => {
	const filters = [
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
							component: 'OtherFish',
						},
					],
				},
			],
		},
		{ label: 'Dinner', value: 'dinner' },
	];

	return <FilterPicker filters={ filters } path={ path } query={ query } />;
};
```

### Props

* `filters` (required): An array of filters and subFilters to construct the menu
* `path` (required): The `path` parameter supplied by React-Router
* `query`: The query string represented in object form

### `filters` structure

The `filters` prop is an array of filter objects. Each filter object should have the following format:

* `label`: The label for this filter. Optional only for custom component filters.
* `subFilters`: An array of more filter objects that act as "children" to this item. This set of filters is shown if the parent filter is clicked.
* `value` (required): The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
* `path`: An array representing the "path" to this filter, if nested. See the Lunch > Pescatarian > Snapper example above.
* `component`: A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
