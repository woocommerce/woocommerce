/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCouponLabels } from 'lib/async-requests';

export const charts = [
	{
		key: 'orders_count',
		label: __( 'Discounted Orders', 'wc-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
	{
		key: 'amount',
		label: __( 'Amount', 'wc-admin' ),
		order: 'desc',
		orderby: 'amount',
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
							getLabels: getCouponLabels,
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
					getLabels: getCouponLabels,
					labels: {
						title: __( 'Compare Coupon Codes', 'wc-admin' ),
						update: __( 'Compare', 'wc-admin' ),
						helpText: __( 'Select at least two coupon codes to compare', 'wc-admin' ),
					},
				},
			},
		],
	},
];
