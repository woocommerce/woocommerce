/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const STOCK_REPORT_FILTERS_FILTER = 'woocommerce_admin_stock_report_filters';
const STOCK_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_stock_report_advanced_filters';

export const showDatePicker = false;

/**
 * Stock Report Advanced Filters.
 *
 * @filter woocommerce_admin_stock_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	STOCK_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Products Match <select/> Filters',
			'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
	}
);

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Stock Report Filters.
 *
 * @filter woocommerce_admin_stock_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( STOCK_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce' ),
		staticParams: [ 'paged', 'per_page' ],
		param: 'type',
		showFilters: () => true,
		filters: [
			{ label: __( 'All products', 'woocommerce' ), value: 'all' },
			{
				label: __( 'Out of stock', 'woocommerce' ),
				value: 'outofstock',
			},
			{
				label: __( 'Low stock', 'woocommerce' ),
				value: 'lowstock',
			},
			{ label: __( 'In stock', 'woocommerce' ), value: 'instock' },
			{
				label: __( 'On backorder', 'woocommerce' ),
				value: 'onbackorder',
			},
		],
	},
	{
		label: __( 'Filter by', 'woocommerce' ),
		staticParams: [ 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => Object.keys( advancedFilters.filters ).length,
		filters: [
			{ label: __( 'All Products', 'woocommerce' ), value: 'all' },
			{
				label: __( 'Advanced Filters', 'woocommerce' ),
				value: 'advanced',
			},
		],
	},
] );
