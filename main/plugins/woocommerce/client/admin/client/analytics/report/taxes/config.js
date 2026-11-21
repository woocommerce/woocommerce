/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { NAMESPACE } from '@woocommerce/data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from '../../../lib/async-requests';
import { getTaxCode } from './utils';
import { getLocationLabels, locationsAutocompleter } from './locations';

const TAXES_REPORT_CHARTS_FILTER = 'woocommerce_admin_taxes_report_charts';
const TAXES_REPORT_FILTERS_FILTER = 'woocommerce_admin_taxes_report_filters';
const TAXES_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_taxes_report_advanced_filters';

const { addCesSurveyForAnalytics } = dispatch( CES_STORE_KEY );

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Taxes Report charts filter.
 *
 * @filter woocommerce_admin_taxes_report_charts
 * @param {Array.<chart>} charts Report charts.
 */
export const charts = applyFilters( TAXES_REPORT_CHARTS_FILTER, [
	{
		key: 'total_tax',
		label: __( 'Total tax', 'woocommerce' ),
		order: 'desc',
		orderby: 'total_tax',
		type: 'currency',
	},
	{
		key: 'order_tax',
		label: __( 'Order tax', 'woocommerce' ),
		order: 'desc',
		orderby: 'order_tax',
		type: 'currency',
	},
	{
		key: 'shipping_tax',
		label: __( 'Shipping tax', 'woocommerce' ),
		order: 'desc',
		orderby: 'shipping_tax',
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

/**
 * Taxes Report Advanced Filters.
 *
 * @filter woocommerce_admin_taxes_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	TAXES_REPORT_ADVANCED_FILTERS_FILTER,
	{
		filters: {
			location: {
				labels: {
					add: __( 'Location', 'woocommerce' ),
					placeholder: __( 'Search', 'woocommerce' ),
					remove: __( 'Remove location filter', 'woocommerce' ),
					rule: __( 'Select a location filter match', 'woocommerce' ),
					/* translators: A sentence describing a Location filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Location</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select location', 'woocommerce' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to tax codes of a given location or locations. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Includes', 'locations', 'woocommerce' ),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to tax codes outside a given location or locations. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Excludes', 'locations', 'woocommerce' ),
					},
				],
				input: {
					component: 'Search',
					type: 'custom',
					autocompleter: locationsAutocompleter,
					getLabels: getLocationLabels,
				},
			},
		},
		title: _x(
			'Taxes match <select/> filters',
			'A sentence describing filters for Taxes. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
	}
);

const filterValues = [
	{ label: __( 'All taxes', 'woocommerce' ), value: 'all' },
	{
		label: __( 'Comparison', 'woocommerce' ),
		value: 'compare-taxes',
		chartMode: 'item-comparison',
		settings: {
			param: 'taxes',
			getLabels: getRequestByIdString(
				NAMESPACE + '/taxes',
				( tax ) => ( {
					id: tax.id,
					key: tax.id,
					label: getTaxCode( tax ),
				} )
			),
			labels: {
				helpText: __(
					'Check at least two tax codes below to compare',
					'woocommerce'
				),
				title: __( 'Compare Tax Codes', 'woocommerce' ),
				update: __( 'Compare', 'woocommerce' ),
			},
			searchProps: {
				type: 'taxes',
				placeholder: __(
					'Search for tax codes to compare',
					'woocommerce'
				),
			},
			onClick: addCesSurveyForAnalytics,
		},
	},
];

if ( Object.keys( advancedFilters.filters ).length ) {
	filterValues.push( {
		label: __( 'Advanced filters', 'woocommerce' ),
		value: 'advanced',
	} );
}

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Coupons Report Filters.
 *
 * @filter woocommerce_admin_taxes_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( TAXES_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: filterValues,
	},
] );
