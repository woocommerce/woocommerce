/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	getProductLabels,
	getVariationLabels,
} from '../../../lib/async-requests';
import { STORE_KEY as CES_STORE_KEY } from '../../../customer-effort-score-tracks/data/constants';

const PRODUCTS_REPORT_CHARTS_FILTER =
	'woocommerce_admin_products_report_charts';
const PRODUCTS_REPORT_FILTERS_FILTER =
	'woocommerce_admin_products_report_filters';
const PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_products_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

export const charts = applyFilters( PRODUCTS_REPORT_CHARTS_FILTER, [
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

const filterConfig = {
	label: __( 'Show', 'woocommerce-admin' ),
	staticParams: [ 'chartType', 'paged', 'per_page' ],
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
							placeholder: __(
								'Type to search for a product',
								'woocommerce-admin'
							),
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
					helpText: __(
						'Check at least two products below to compare',
						'woocommerce-admin'
					),
					placeholder: __(
						'Search for products to compare',
						'woocommerce-admin'
					),
					title: __( 'Compare Products', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
				onClick: addCesSurveyForAnalytics,
			},
		},
	],
};

const variationsConfig = {
	showFilters: ( query ) =>
		query.filter === 'single_product' &&
		!! query.products &&
		query[ 'is-variable' ],
	staticParams: [ 'filter', 'products', 'chartType', 'paged', 'per_page' ],
	param: 'filter-variations',
	filters: [
		{
			label: __( 'All Variations', 'woocommerce-admin' ),
			chartMode: 'item-comparison',
			value: 'all',
		},
		{
			label: __( 'Single Variation', 'woocommerce-admin' ),
			value: 'select_variation',
			subFilters: [
				{
					component: 'Search',
					value: 'single_variation',
					path: [ 'select_variation' ],
					settings: {
						type: 'variations',
						param: 'variations',
						getLabels: getVariationLabels,
						labels: {
							placeholder: __(
								'Type to search for a variation',
								'woocommerce-admin'
							),
							button: __(
								'Single Variation',
								'woocommerce-admin'
							),
						},
					},
				},
			],
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
					helpText: __(
						'Check at least two variations below to compare',
						'woocommerce-admin'
					),
					placeholder: __(
						'Search for variations to compare',
						'woocommerce-admin'
					),
					title: __( 'Compare Variations', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
			},
		},
	],
};

export const advancedFilters = applyFilters(
	PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Products Match {{select /}} Filters',
			'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce-admin'
		),
	}
);

if ( Object.keys( advancedFilters.filters ).length ) {
	filterConfig.filters.push( {
		label: __( 'Advanced Filters', 'woocommerce-admin' ),
		value: 'advanced',
	} );
	variationsConfig.filters.push( {
		label: __( 'Advanced Filters', 'woocommerce-admin' ),
		value: 'advanced',
	} );
}

export const filters = applyFilters( PRODUCTS_REPORT_FILTERS_FILTER, [
	filterConfig,
	variationsConfig,
] );
