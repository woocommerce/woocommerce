/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCategoryLabels } from '../../../lib/async-requests';
import { STORE_KEY as CES_STORE_KEY } from '../../../customer-effort-score-tracks/data/constants';

const CATEGORY_REPORT_CHARTS_FILTER =
	'woocommerce_admin_categories_report_charts';
const CATEGORY_REPORT_FILTERS_FILTER =
	'woocommerce_admin_categories_report_filters';
const CATEGORY_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_category_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Category Report charts filter.
 *
 * @filter woocommerce_admin_categories_report_charts
 * @param {Array.<chart>} charts Category Report charts.
 */
export const charts = applyFilters( CATEGORY_REPORT_CHARTS_FILTER, [
	{
		key: 'items_sold',
		label: __( 'Items sold', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net sales', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
] );

/**
 * Category Report Advanced Filters.
 *
 * @filter woocommerce_admin_category_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	CATEGORY_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Categories match {{select /}} filters',
			'A sentence describing filters for Categories. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce-admin'
		),
	}
);

const filterValues = [
	{
		label: __( 'All categories', 'woocommerce-admin' ),
		value: 'all',
	},
	{
		label: __( 'Single category', 'woocommerce-admin' ),
		value: 'select_category',
		chartMode: 'item-comparison',
		subFilters: [
			{
				component: 'Search',
				value: 'single_category',
				chartMode: 'item-comparison',
				path: [ 'select_category' ],
				settings: {
					type: 'categories',
					param: 'categories',
					getLabels: getCategoryLabels,
					labels: {
						placeholder: __(
							'Type to search for a category',
							'woocommerce-admin'
						),
						button: __( 'Single Category', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
	{
		label: __( 'Comparison', 'woocommerce-admin' ),
		value: 'compare-categories',
		chartMode: 'item-comparison',
		settings: {
			type: 'categories',
			param: 'categories',
			getLabels: getCategoryLabels,
			labels: {
				helpText: __(
					'Check at least two categories below to compare',
					'woocommerce-admin'
				),
				placeholder: __(
					'Search for categories to compare',
					'woocommerce-admin'
				),
				title: __( 'Compare Categories', 'woocommerce-admin' ),
				update: __( 'Compare', 'woocommerce-admin' ),
			},
			onClick: addCesSurveyForAnalytics,
		},
	},
];

if ( Object.keys( advancedFilters.filters ).length ) {
	filterValues.push( {
		label: __( 'Advanced filters', 'woocommerce-admin' ),
		value: 'advanced',
	} );
}

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Category Report Filters.
 *
 * @filter woocommerce_admin_categories_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( CATEGORY_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: filterValues,
	},
] );
