/**
 * External dependencies
 */
import { FilterPicker } from '@woocommerce/components';

const path =
	new URL( document.location ).searchParams.get( 'path' ) || '/devdocs';
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

export default () => (
	<FilterPicker config={ config } path={ path } query={ query } />
);
