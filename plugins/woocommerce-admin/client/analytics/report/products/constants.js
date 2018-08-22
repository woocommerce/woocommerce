/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const filters = [
	{ label: __( 'All Products', 'wc-admin' ), value: 'all' },
	{
		label: __( 'Single Product', 'wc-admin' ),
		value: 'single',
		subFilters: [
			{
				component: 'Search',
				value: 'single_search',
				path: [ 'single' ],
			},
		],
	},
	{ label: __( 'Top Products by Items Sold', 'wc-admin' ), value: 'top_items' },
	{ label: __( 'Top Products by Gross Sales', 'wc-admin' ), value: 'top_sales' },
	{ label: __( 'Comparison', 'wc-admin' ), value: 'compare' },
];
