/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getProductLabels, getCategoryLabels, getVariationLabels } from 'lib/async-requests';

export const charts = [
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		type: 'number',
	},
];

const filterConfig = {
	label: __( 'Show', 'wc-admin' ),
	staticParams: [],
	param: 'filter',
	showFilters: () => true,
	filters: [
		{ label: __( 'All Products', 'wc-admin' ), value: 'all' },
		{
			label: __( 'Single Product', 'wc-admin' ),
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
							placeholder: __( 'Type to search for a product', 'wc-admin' ),
							button: __( 'Single Product', 'wc-admin' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Single Product Category', 'wc-admin' ),
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
							placeholder: __( 'Type to search for a product category', 'wc-admin' ),
							button: __( 'Single Product Category', 'wc-admin' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Product Comparison', 'wc-admin' ),
			value: 'compare-products',
			chartMode: 'item-comparison',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: getProductLabels,
				labels: {
					helpText: __( 'Select at least two products to compare', 'wc-admin' ),
					placeholder: __( 'Search for products to compare', 'wc-admin' ),
					title: __( 'Compare Products', 'wc-admin' ),
					update: __( 'Compare', 'wc-admin' ),
				},
			},
		},
		{
			label: __( 'Product Category Comparison', 'wc-admin' ),
			value: 'compare-categories',
			chartMode: 'item-comparison',
			settings: {
				type: 'categories',
				param: 'categories',
				getLabels: getCategoryLabels,
				labels: {
					helpText: __( 'Select at least two product categories to compare', 'wc-admin' ),
					placeholder: __( 'Search for product categories to compare', 'wc-admin' ),
					title: __( 'Compare Product Categories', 'wc-admin' ),
					update: __( 'Compare', 'wc-admin' ),
				},
			},
		},
		{
			label: __( 'Top Products by Items Sold', 'wc-admin' ),
			value: 'top_items',
			chartMode: 'item-comparison',
			query: { orderby: 'items_sold', order: 'desc', chart: 'items_sold' },
		},
		{
			label: __( 'Top Products by Net Revenue', 'wc-admin' ),
			value: 'top_sales',
			chartMode: 'item-comparison',
			query: { orderby: 'net_revenue', order: 'desc', chart: 'net_revenue' },
		},
	],
};

const variationsConfig = {
	showFilters: query =>
		'single_product' === query.filter && !! query.products && query[ 'is-variable' ],
	staticParams: [ 'filter', 'products' ],
	param: 'filter-variations',
	filters: [
		{ label: __( 'All Variations', 'wc-admin' ), chartMode: 'item-comparison', value: 'all' },
		{
			label: __( 'Comparison', 'wc-admin' ),
			chartMode: 'item-comparison',
			value: 'compare-variations',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: getVariationLabels,
				labels: {
					helpText: __( 'Select at least two variations to compare', 'wc-admin' ),
					placeholder: __( 'Search for variations to compare', 'wc-admin' ),
					title: __( 'Compare Variations', 'wc-admin' ),
					update: __( 'Compare', 'wc-admin' ),
				},
			},
		},
		{
			label: __( 'Top Variations by Items Sold', 'wc-admin' ),
			chartMode: 'item-comparison',
			value: 'top_items',
			query: { orderby: 'items_sold', order: 'desc', chart: 'item_sold' },
		},
		{
			label: __( 'Top Variations by Net Revenue', 'wc-admin' ),
			chartMode: 'item-comparison',
			value: 'top_sales',
			query: { orderby: 'net_revenue', order: 'desc', chart: 'net_revenue' },
		},
	],
};

export const filters = [ filterConfig, variationsConfig ];
