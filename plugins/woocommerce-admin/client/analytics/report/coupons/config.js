/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCouponLabels } from '../../../lib/async-requests';
import { STORE_KEY as CES_STORE_KEY } from '../../../customer-effort-score-tracks/data/constants';

const COUPON_REPORT_CHARTS_FILTER = 'woocommerce_admin_coupons_report_charts';
const COUPON_REPORT_FILTERS_FILTER = 'woocommerce_admin_coupons_report_filters';
const COUPON_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_coupon_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

export const charts = applyFilters( COUPON_REPORT_CHARTS_FILTER, [
	{
		key: 'orders_count',
		label: __( 'Discounted Orders', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
	{
		key: 'amount',
		label: __( 'Amount', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'amount',
		type: 'currency',
	},
] );

export const advancedFilters = applyFilters(
	COUPON_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Coupons Match {{select /}} Filters',
			'A sentence describing filters for Coupons. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce-admin'
		),
	}
);

const filterValues = [
	{ label: __( 'All Coupons', 'woocommerce-admin' ), value: 'all' },
	{
		label: __( 'Single Coupon', 'woocommerce-admin' ),
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
						placeholder: __(
							'Type to search for a coupon',
							'woocommerce-admin'
						),
						button: __( 'Single Coupon', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
	{
		label: __( 'Comparison', 'woocommerce-admin' ),
		value: 'compare-coupons',
		settings: {
			type: 'coupons',
			param: 'coupons',
			getLabels: getCouponLabels,
			labels: {
				title: __( 'Compare Coupon Codes', 'woocommerce-admin' ),
				update: __( 'Compare', 'woocommerce-admin' ),
				helpText: __(
					'Check at least two coupon codes below to compare',
					'woocommerce-admin'
				),
			},
			onClick: addCesSurveyForAnalytics,
		},
	},
];

if ( Object.keys( advancedFilters.filters ).length ) {
	filterValues.push( {
		label: __( 'Advanced Filters', 'woocommerce-admin' ),
		value: 'advanced',
	} );
}

export const filters = applyFilters( COUPON_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: filterValues,
	},
] );
