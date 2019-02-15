/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCategoryLabels } from 'lib/async-requests';

export const charts = [
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'wc-admin' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'wc-admin' ),
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'wc-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Categories', 'wc-admin' ), value: 'all' },
			{
				label: __( 'Single Category', 'wc-admin' ),
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
								placeholder: __( 'Type to search for a category', 'wc-admin' ),
								button: __( 'Single Category', 'wc-admin' ),
							},
						},
					},
				],
			},
			{
				label: __( 'Comparison', 'wc-admin' ),
				value: 'compare-categories',
				chartMode: 'item-comparison',
				settings: {
					type: 'categories',
					param: 'categories',
					getLabels: getCategoryLabels,
					labels: {
						helpText: __( 'Select at least two categories to compare', 'wc-admin' ),
						placeholder: __( 'Search for categories to compare', 'wc-admin' ),
						title: __( 'Compare Categories', 'wc-admin' ),
						update: __( 'Compare', 'wc-admin' ),
					},
				},
			},
			{
				label: __( 'Top Categories by Items Sold', 'wc-admin' ),
				value: 'top_items',
				chartMode: 'item-comparison',
				query: { orderby: 'items_sold', order: 'desc' },
			},
			{
				label: __( 'Top Categories by Net Revenue', 'wc-admin' ),
				value: 'top_revenue',
				chartMode: 'item-comparison',
				query: { orderby: 'net_revenue', order: 'desc' },
			},
		],
	},
];
