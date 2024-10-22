/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import { getCouponLabels } from '../../../lib/async-requests';

const COUPON_REPORT_CHARTS_FILTER = 'woocommerce_admin_coupons_report_charts';
const COUPON_REPORT_FILTERS_FILTER = 'woocommerce_admin_coupons_report_filters';
const COUPON_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_coupon_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Coupons Report charts filter.
 *
 * @filter woocommerce_admin_coupons_report_charts
 * @param {Array.<chart>} charts Report charts.
 */
export const charts = applyFilters( COUPON_REPORT_CHARTS_FILTER, [
	{
		key: 'orders_count',
		label: __( 'Discounted orders', 'woocommerce' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
	{
		key: 'amount',
		label: __( 'Amount', 'woocommerce' ),
		order: 'desc',
		orderby: 'amount',
		type: 'currency',
	},
] );

/**
 * Coupons Report Advanced Filters.
 *
 * @filter woocommerce_admin_coupon_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	COUPON_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Coupons match <select/> filters',
			'A sentence describing filters for Coupons. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
	}
);

const filterValues = [
	{ label: __( 'All coupons', 'woocommerce' ), value: 'all' },
	{
		label: __( 'Single coupon', 'woocommerce' ),
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
							'woocommerce'
						),
						button: __( 'Single Coupon', 'woocommerce' ),
					},
				},
			},
		],
	},
	{
		label: __( 'Comparison', 'woocommerce' ),
		value: 'compare-coupons',
		settings: {
			type: 'coupons',
			param: 'coupons',
			getLabels: getCouponLabels,
			labels: {
				title: __( 'Compare Coupon Codes', 'woocommerce' ),
				update: __( 'Compare', 'woocommerce' ),
				helpText: __(
					'Check at least two coupon codes below to compare',
					'woocommerce'
				),
			},
			onClick: addCesSurveyForAnalytics,
		},
	},
];

if ( Object.keys( advancedFilters.filters ).length ) {
	filterValues.push( {
		label: __( 'Advanced filters', 'woocommerce' ),
		value: 'advanced',
	} );
}

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Coupons Report Filters.
 *
 * @filter woocommerce_admin_coupons_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( COUPON_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: filterValues,
	},
] );
