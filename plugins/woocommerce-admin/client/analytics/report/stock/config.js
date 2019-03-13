/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const showDatePicker = false;

export const filters = [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [],
		param: 'type',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Products', 'woocommerce-admin' ), value: 'all' },
			{ label: __( 'Out of Stock', 'woocommerce-admin' ), value: 'outofstock' },
			{ label: __( 'Low Stock', 'woocommerce-admin' ), value: 'lowstock' },
			{ label: __( 'In Stock', 'woocommerce-admin' ), value: 'instock' },
			{ label: __( 'On Backorder', 'woocommerce-admin' ), value: 'onbackorder' },
		],
	},
];
