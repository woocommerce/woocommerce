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
		key: 'orders_count',
		label: __( 'Discounted Orders', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'amount',
		label: __( 'Amount', 'wc-admin' ),
		type: 'currency',
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
				label: __( 'Single Coupon', 'wc-admin' ),
				value: 'select_coupon',
				chartMode: 'item-comparison',
				subFilters: [
					{
						component: 'Search',
						value: 'single_coupon',
						chartMode: 'item-comparison',
						path: [ 'select_coupon' ],
						settings: {
							type: 'coupons',
							param: 'coupons',
							getLabels: getRequestByIdString( NAMESPACE + 'coupons', coupon => ( {
								id: coupon.id,
								label: coupon.code,
							} ) ),
							labels: {
								placeholder: __( 'Type to search for a coupon', 'wc-admin' ),
								button: __( 'Single Coupon', 'wc-admin' ),
							},
						},
					},
				],
			},
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
						helpText: __( 'Select at least two coupon codes to compare', 'wc-admin' ),
					},
				},
			},
			{ label: __( 'Top Coupons by Discounted Orders', 'wc-admin' ), value: 'top_orders' },
			{ label: __( 'Top Coupons by Amount Discounted', 'wc-admin' ), value: 'top_discount' },
		],
	},
];
