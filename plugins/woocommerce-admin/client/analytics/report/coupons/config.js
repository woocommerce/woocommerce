/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

export const charts = [
	{
		key: 'avg_items_per_order',
		label: __( 'Average Items Per Order', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'avg_order_value',
		label: __( 'Average Order Value', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'coupons',
		label: __( 'Coupons', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'num_items_sold',
		label: __( 'Number of Items Sold', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'num_new_customers',
		label: __( 'Number of New Customers', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'num_returning_customers',
		label: __( 'Number of Returning Customers', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'products',
		label: __( 'Products', 'wc-admin' ),
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
			{ label: __( 'All Coupons', 'wc-admin' ), value: 'all' },
			{
				label: __( 'Comparison', 'wc-admin' ),
				value: 'compare-coupons',
				settings: {
					type: 'coupons',
					param: 'coupons',
					getLabels: getRequestByIdString( NAMESPACE + 'coupons', coupon => ( {
						id: coupon.id,
						label: coupon.code,
					} ) ),
					labels: {
						title: __( 'Compare Coupon Codes', 'wc-admin' ),
						update: __( 'Compare', 'wc-admin' ),
					},
				},
			},
			{ label: __( 'Top Coupons by Discounted Orders', 'wc-admin' ), value: 'top_orders' },
			{ label: __( 'Top Coupons by Gross Discounted', 'wc-admin' ), value: 'top_discount' },
		],
	},
];
