/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

export const charts = [
	{
		key: 'downloads_count',
		label: __( 'Downloads', 'wc-admin' ),
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Downloads', 'wc-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
		],
	},
];

/*eslint-disable max-len*/
export const advancedFilters = {
	title: _x(
		'Downloads Match {{select /}} Filters',
		'A sentence describing filters for Downloads. See screen shot for context: https://cloudup.com/ccxhyH2mEDg',
		'wc-admin'
	),
	filters: {
		product: {
			labels: {
				add: __( 'Product', 'wc-admin' ),
				placeholder: __( 'Search', 'wc-admin' ),
				remove: __( 'Remove product filter', 'wc-admin' ),
				rule: __( 'Select a product filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
				title: __( 'Product {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select product', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to products including a given product(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Includes', 'products', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to products excluding a products(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Excludes', 'products', 'wc-admin' ),
				},
			],
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
/*eslint-enable max-len*/
