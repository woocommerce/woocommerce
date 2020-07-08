/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getCategoryLabels } from 'lib/async-requests';

const CATEGORY_REPORT_CHARTS_FILTER =
	'woocommerce_admin_categories_report_charts';
const CATEGORY_REPORT_FILTERS_FILTER =
	'woocommerce_admin_categories_report_filters';
const CATEGORY_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_category_report_advanced_filters';

export const charts = applyFilters( CATEGORY_REPORT_CHARTS_FILTER, [
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Sales', 'woocommerce-admin' ),
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

export const filters = applyFilters( CATEGORY_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{
				label: __( 'All Categories', 'woocommerce-admin' ),
				value: 'all',
			},
			{
				label: __( 'Single Category', 'woocommerce-admin' ),
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
								button: __(
									'Single Category',
									'woocommerce-admin'
								),
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
				},
			},
		],
	},
] );

export const advancedFilters = applyFilters(
	CATEGORY_REPORT_ADVANCED_FILTERS_FILTER,
	{}
);
