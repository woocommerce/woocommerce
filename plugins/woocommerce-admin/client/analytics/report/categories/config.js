/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const charts = [
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'gross_revenue',
		label: __( 'Gross Revenue', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'products_count',
		label: __( 'Number of Products', 'wc-admin' ),
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Categories', 'wc-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
		],
	},
];
