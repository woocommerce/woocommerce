/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getProductLabels, getVariationLabels } from 'lib/async-requests';

const PRODUCTS_REPORT_CHART_FILTER = 'woocommerce_admin_products_report_chart_filter';

export const charts = applyFilters( PRODUCTS_REPORT_CHART_FILTER, [
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
] );

const filterConfig = {
	label: __( 'Show', 'woocommerce-admin' ),
	staticParams: [],
	param: 'filter',
	showFilters: () => true,
	filters: [
		{ label: __( 'All Products', 'woocommerce-admin' ), value: 'all' },
		{
			label: __( 'Single Product', 'woocommerce-admin' ),
			value: 'select_product',
			chartMode: 'item-comparison',
			subFilters: [
				{
					component: 'Search',
					value: 'single_product',
					chartMode: 'item-comparison',
					path: [ 'select_product' ],
					settings: {
						type: 'products',
						param: 'products',
						getLabels: getProductLabels,
						labels: {
							placeholder: __( 'Type to search for a product', 'woocommerce-admin' ),
							button: __( 'Single Product', 'woocommerce-admin' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'woocommerce-admin' ),
			value: 'compare-products',
			chartMode: 'item-comparison',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: getProductLabels,
				labels: {
					helpText: __( 'Check at least two products below to compare', 'woocommerce-admin' ),
					placeholder: __( 'Search for products to compare', 'woocommerce-admin' ),
					title: __( 'Compare Products', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
			},
		},
	],
};

const variationsConfig = {
	showFilters: query =>
		'single_product' === query.filter && !! query.products && query[ 'is-variable' ],
	staticParams: [ 'filter', 'products' ],
	param: 'filter-variations',
	filters: [
		{
			label: __( 'All Variations', 'woocommerce-admin' ),
			chartMode: 'item-comparison',
			value: 'all',
		},
		{
			label: __( 'Comparison', 'woocommerce-admin' ),
			chartMode: 'item-comparison',
			value: 'compare-variations',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: getVariationLabels,
				labels: {
					helpText: __( 'Check at least two variations below to compare', 'woocommerce-admin' ),
					placeholder: __( 'Search for variations to compare', 'woocommerce-admin' ),
					title: __( 'Compare Variations', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
			},
		},
	],
};

export const filters = [ filterConfig, variationsConfig ];
