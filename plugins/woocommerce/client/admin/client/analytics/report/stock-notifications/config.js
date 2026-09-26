/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const STOCK_NOTIFICATIONS_REPORT_CHARTS_FILTER =
	'woocommerce_admin_stock_notifications_report_charts';
const STOCK_NOTIFICATIONS_REPORT_FILTERS_FILTER =
	'woocommerce_admin_stock_notifications_report_filters';
const STOCK_NOTIFICATIONS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_stock_notifications_report_advanced_filters';

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Stock Notifications Report charts filter.
 *
 * `active_signups` is left out: it is a point-in-time count with no interval series, so its chart would be a flat zero line.
 *
 * @filter woocommerce_admin_stock_notifications_report_charts
 * @param {Array.<chart>} charts Report charts.
 */
export const charts = applyFilters( STOCK_NOTIFICATIONS_REPORT_CHARTS_FILTER, [
	{
		key: 'signups',
		label: __( 'Sign-ups', 'woocommerce' ),
		order: 'desc',
		orderby: 'signups',
		type: 'number',
	},
	{
		key: 'notifications_sent',
		label: __( 'Notifications sent', 'woocommerce' ),
		type: 'number',
	},
	{
		key: 'customers',
		label: __( 'Customers', 'woocommerce' ),
		order: 'desc',
		orderby: 'customers',
		type: 'number',
	},
] );

/**
 * Stock Notifications Report Advanced Filters.
 *
 * @filter woocommerce_admin_stock_notifications_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	STOCK_NOTIFICATIONS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Stock notifications match <select/> filters',
			'A sentence describing filters for Stock notifications. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
	}
);

const filterValues = [];

if ( Object.keys( advancedFilters.filters ).length ) {
	filterValues.push( {
		label: __( 'All products', 'woocommerce' ),
		value: 'all',
	} );
	filterValues.push( {
		label: __( 'Advanced filters', 'woocommerce' ),
		value: 'advanced',
	} );
}

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Stock Notifications Report Filters.
 *
 * @filter woocommerce_admin_stock_notifications_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters(
	STOCK_NOTIFICATIONS_REPORT_FILTERS_FILTER,
	[
		{
			label: __( 'Show', 'woocommerce' ),
			staticParams: [ 'chartType', 'paged', 'per_page' ],
			param: 'filter',
			showFilters: () => filterValues.length > 0,
			filters: filterValues,
		},
	]
);
