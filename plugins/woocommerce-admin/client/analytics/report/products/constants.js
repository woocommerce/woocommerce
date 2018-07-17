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
				label: __( 'Single Product', 'wc-admin' ),
				component: 'Search',
				value: 'single_search',
			},
		],
	},
	{ label: __( 'Top Products by Items Sold', 'wc-admin' ), value: 'top_items' },
	{ label: __( 'Top Products by Gross Sales', 'wc-admin' ), value: 'top_sales' },
	{ label: __( 'Comparison', 'wc-admin' ), value: 'compare' },
];

export const filterPaths = {
	all: [],
	single: [],
	single_search: [ 'single' ],
	top_items: [],
	top_sales: [],
	compare: [],
};
