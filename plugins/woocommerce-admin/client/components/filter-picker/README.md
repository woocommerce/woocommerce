Filter Picker
===

Modify a url query parameter via a dropdown selection of configurable options. This component manipulates the `filter` query parameter.

## Usage

```jsx
import FilterPicker from 'components/filter-picker';

const renderFilterPicker = ( { path, query } ) {
	const filters = [
		{ label: 'Breakfast', value: 'breakfast' },
		{ label: 'Lunch', value: 'lunch', subFilters: [
			{ label: 'Meat', value: 'meat' },
			{ label: 'Vegan', value: 'vegan' },
			{ label: 'Pescatarian', value: 'fish', subFilters: [
				{ label: 'Snapper', value: 'snapper' },
				{ label: 'Cod', value: 'cod' },
			] },
			// Specify a custom component to render (Work in Progress)
			{ label: 'Other', value: 'other_fish', component: 'OtherFish' },
		] },
		{ label: 'Dinner', value: 'dinner' },
	];
	
	const filterPaths = {
		breakfast: [],
		lunch: [],
		dinner: [],
		meat: [ 'lunch' ],
		vegan: [ 'lunch' ],
		fish: [ 'lunch' ],
		snapper: [ 'lunch', 'fish' ],
		cod: [ 'lunch', 'fish' ],
		other_fish: [ 'lunch', 'fish' ],
	};
	
	return (
		<FilterPicker
			query={ query }
			path={ path }
			filters={ filters }
			filterPaths={ filterPaths }
		/>
	);
}
```

### Props

* `query` (required): The query string represented in object form
* `path` (required): Parameter supplied by React-Router
* `filters` (required): An array of filters and subFilters to construct the menu
* `filterPaths` (required): A map of representing the structure of the tree. Required for faster lookups than searches
