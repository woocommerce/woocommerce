/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const showDatePicker = false;

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'type',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Products', 'wc-admin' ), value: 'all' },
			{ label: __( 'Out of Stock', 'wc-admin' ), value: 'outofstock' },
			{ label: __( 'Low Stock', 'wc-admin' ), value: 'lowstock' },
			{ label: __( 'In Stock', 'wc-admin' ), value: 'instock' },
			{ label: __( 'On Backorder', 'wc-admin' ), value: 'onbackorder' },
		],
	},
];
