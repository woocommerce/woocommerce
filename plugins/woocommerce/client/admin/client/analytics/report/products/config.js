/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import {
	getProductLabels,
	getVariationLabels,
} from '../../../lib/async-requests';

const PRODUCTS_REPORT_CHARTS_FILTER =
	'woocommerce_admin_products_report_charts';
const PRODUCTS_REPORT_FILTERS_FILTER =
	'woocommerce_admin_products_report_filters';
const PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_products_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Products Report charts filter.
 *
 * @filter woocommerce_admin_products_report_charts
 * @param {Array.<chart>} charts Report charts.
 */
export const charts = applyFilters( PRODUCTS_REPORT_CHARTS_FILTER, [
	{
		key: 'items_sold',
		label: __( 'Items sold', 'woocommerce' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net sales', 'woocommerce' ),
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders', 'woocommerce' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
] );

const filterConfig = {
	label: __( 'Show', 'woocommerce' ),
	staticParams: [ 'chartType', 'paged', 'per_page' ],
	param: 'filter',
	showFilters: () => true,
	filters: [
		{ label: __( 'All products', 'woocommerce' ), value: 'all' },
		{
			label: __( 'Single product', 'woocommerce' ),
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
								'woocommerce'
							),
							button: __( 'Single product', 'woocommerce' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'woocommerce' ),
			value: 'compare-products',
			chartMode: 'item-comparison',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: getProductLabels,
				labels: {
					helpText: __(
						'Check at least two products below to compare',
						'woocommerce'
					),
					placeholder: __(
						'Search for products to compare',
						'woocommerce'
					),
					title: __( 'Compare Products', 'woocommerce' ),
					update: __( 'Compare', 'woocommerce' ),
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
			label: __( 'All variations', 'woocommerce' ),
			chartMode: 'item-comparison',
			value: 'all',
		},
		{
			label: __( 'Single variation', 'woocommerce' ),
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
								'woocommerce'
							),
							button: __( 'Single variation', 'woocommerce' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'woocommerce' ),
			chartMode: 'item-comparison',
			value: 'compare-variations',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: getVariationLabels,
				labels: {
					helpText: __(
						'Check at least two variations below to compare',
						'woocommerce'
					),
					placeholder: __(
						'Search for variations to compare',
						'woocommerce'
					),
					title: __( 'Compare Variations', 'woocommerce' ),
					update: __( 'Compare', 'woocommerce' ),
				},
			},
		},
	],
};

/**
 * Products Report Advanced Filters.
 *
 * @filter woocommerce_admin_products_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {},
		title: _x(
			'Products Match <select/> Filters',
			'A sentence describing filters for Products. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
	}
);

if ( Object.keys( advancedFilters.filters ).length ) {
	filterConfig.filters.push( {
		label: __( 'Advanced Filters', 'woocommerce' ),
		value: 'advanced',
	} );
	variationsConfig.filters.push( {
		label: __( 'Advanced Filters', 'woocommerce' ),
		value: 'advanced',
	} );
}

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Products Report Filters.
 *
 * @filter woocommerce_admin_products_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( PRODUCTS_REPORT_FILTERS_FILTER, [
	filterConfig,
	variationsConfig,
] );
