/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { getRequestByIdString } from '../../../lib/async-requests';
import { NAMESPACE } from '../../../store/constants';

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Registered Customers', 'wc-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
		],
	},
];

/*eslint-disable max-len*/
export const advancedFilters = {
	title: _x(
		'Customers Match {{select /}} Filters',
		'A sentence describing filters for Customers. See screen shot for context: https://cloudup.com/cCsm3GeXJbE',
		'wc-admin'
	),
	filters: {
		name: {
			labels: {
				add: __( 'Name', 'wc-admin' ),
				placeholder: __( 'Search customer name', 'wc-admin' ),
				remove: __( 'Remove customer name filter', 'wc-admin' ),
				rule: __( 'Select a customer name filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Name {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select customer name', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to customer names including a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Includes', 'customer names', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to customer names excluding a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Excludes', 'customer names', 'wc-admin' ),
				},
			],
			input: {
				// Use products autocompleter for now, see https://github.com/woocommerce/wc-admin/issues/1029 for progress
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
/*eslint-enable max-len*/
