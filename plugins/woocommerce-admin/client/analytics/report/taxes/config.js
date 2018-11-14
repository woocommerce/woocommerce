/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */

export const charts = [
	{
		key: 'order_tax',
		label: __( 'Order Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'total_tax',
		label: __( 'Total Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'shipping_tax',
		label: __( 'Shipping Tax', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [ { label: __( 'All Taxes', 'wc-admin' ), value: 'all' } ],
	},
];
