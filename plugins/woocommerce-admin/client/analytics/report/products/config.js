/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getIdsFromQuery, stringifyQuery } from 'lib/nav-utils';
import { NAMESPACE } from 'store/constants';

export function getProductLabelsById( queryString = '' ) {
	const idList = getIdsFromQuery( queryString );
	if ( idList.length < 1 ) {
		return Promise.resolve( [] );
	}
	const payload = stringifyQuery( {
		include: idList.join( ',' ),
		per_page: idList.length,
	} );
	return apiFetch( { path: `${ NAMESPACE }products${ payload }` } );
}

export function getCategoryLabelsById( queryString = '' ) {
	const idList = getIdsFromQuery( queryString );
	if ( idList.length < 1 ) {
		return Promise.resolve( [] );
	}
	const payload = stringifyQuery( {
		include: idList.join( ',' ),
		per_page: idList.length,
	} );
	return apiFetch( { path: `${ NAMESPACE }products/categories${ payload }` } );
}

export const filters = [
	{ label: __( 'All Products', 'wc-admin' ), value: 'all' },
	{
		label: __( 'Single Product', 'wc-admin' ),
		value: 'single',
		subFilters: [
			{
				component: 'Search',
				value: 'single_search',
				path: [ 'single' ],
			},
		],
	},
	{
		label: __( 'Product Comparison', 'wc-admin' ),
		value: 'compare-product',
		settings: {
			type: 'products',
			param: 'product',
			getLabels: getProductLabelsById,
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
			param: 'product_cat',
			getLabels: getCategoryLabelsById,
			labels: {
				helpText: __( 'Select at least two product categories to compare', 'wc-admin' ),
				placeholder: __( 'Search for product categories to compare', 'wc-admin' ),
				title: __( 'Compare Product Categories', 'wc-admin' ),
				update: __( 'Compare', 'wc-admin' ),
			},
		},
	},
	{ label: __( 'Top Products by Items Sold', 'wc-admin' ), value: 'top_items' },
	{ label: __( 'Top Products by Gross Sales', 'wc-admin' ), value: 'top_sales' },
];
