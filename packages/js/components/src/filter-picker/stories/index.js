/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FilterPicker from '../';

const query = {
	meal: 'breakfast',
};
const config = {
	label: 'Meal',
	staticParams: [],
	param: 'meal',
	showFilters: () => true,
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
						{
							label: 'Snapper',
							value: 'snapper',
							path: [ 'lunch', 'fish' ],
						},
						{
							label: 'Cod',
							value: 'cod',
							path: [ 'lunch', 'fish' ],
						},
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
	],
};

export const Basic = ( {
	path = new URL( document.location ).searchParams.get( 'path' ),
} ) => {
	return <FilterPicker config={ config } path={ path } query={ query } />;
};

export default {
	title: 'WooCommerce Admin/components/FilterPicker',
	component: FilterPicker,
};
