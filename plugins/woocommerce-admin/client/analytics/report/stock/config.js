/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const STOCK_REPORT_FILTERS_FILTER = 'woocommerce_admin_stock_report_filters';
const STOCK_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_stock_report_advanced_filters';

export const showDatePicker = false;

export const advancedFilters = applyFilters(
	STOCK_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Products Match {{select /}} Filters',
			'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce-admin'
		),
	}
);

export const filters = applyFilters( STOCK_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'paged', 'per_page' ],
		param: 'type',
		showFilters: () => true,
		filters: [
			{ label: __( 'All products', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Out of stock', 'woocommerce-admin' ),
				value: 'outofstock',
			},
			{
				label: __( 'Low stock', 'woocommerce-admin' ),
				value: 'lowstock',
			},
			{ label: __( 'In stock', 'woocommerce-admin' ), value: 'instock' },
			{
				label: __( 'On backorder', 'woocommerce-admin' ),
				value: 'onbackorder',
			},
		],
	},
	{
		label: __( 'Filter by', 'woocommerce-admin' ),
		staticParams: [ 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => Object.keys( advancedFilters.filters ).length,
		filters: [
			{ label: __( 'All Products', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Advanced Filters', 'woocommerce-admin' ),
				value: 'advanced',
			},
		],
	},
] );
