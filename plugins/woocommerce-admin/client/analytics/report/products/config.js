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

export const filters = [
	{ label: __( 'All Products', 'wc-admin' ), value: 'all' },
	{
		label: __( 'Single Product', 'wc-admin' ),
		value: 'select_product',
		subFilters: [
			{
				component: 'Search',
				value: 'single_product',
				path: [ 'select_product' ],
				settings: {
					type: 'products',
					param: 'products',
					getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
						id: product.id,
						label: product.name,
					} ) ),
					labels: {
						placeholder: __( 'Type to search for a product', 'wc-admin' ),
						button: __( 'Single Product', 'wc-admin' ),
					},
				},
			},
		],
	},
	{
		label: __( 'Product Comparison', 'wc-admin' ),
		value: 'compare-product',
		settings: {
			type: 'products',
			param: 'products',
			getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
				id: product.id,
				label: product.name,
			} ) ),
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
		value: 'compare-product_cat',
		settings: {
			type: 'product_cats',
			param: 'categories',
			getLabels: getRequestByIdString( NAMESPACE + 'products/categories', category => ( {
				id: category.id,
				label: category.name,
			} ) ),
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
		query: { orderby: 'items_sold', order: 'desc' },
	},
	{
		label: __( 'Top Products by Gross Revenue', 'wc-admin' ),
		value: 'top_sales',
		query: { orderby: 'gross_revenue', order: 'desc' },
	},
];
