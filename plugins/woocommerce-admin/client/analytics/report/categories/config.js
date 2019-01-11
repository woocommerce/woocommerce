/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

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

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Categories', 'wc-admin' ), value: 'all' },
			{
				label: __( 'Comparison', 'wc-admin' ),
				value: 'compare-categories',
				chartMode: 'item-comparison',
				settings: {
					type: 'categories',
					param: 'categories',
					getLabels: getRequestByIdString( NAMESPACE + 'products/categories', cat => ( {
						id: cat.id,
						label: cat.name,
					} ) ),
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
