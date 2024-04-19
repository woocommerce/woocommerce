/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	getCouponLabels,
	getProductLabels,
	getTaxRateLabels,
	getVariationLabels,
} from '../../../lib/async-requests';
import { ORDER_STATUSES } from '~/utils/admin-settings';

const ORDERS_REPORT_CHARTS_FILTER = 'woocommerce_admin_orders_report_charts';
const ORDERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_orders_report_filters';
const ORDERS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_orders_report_advanced_filters';

/**
 * @typedef {import('../index.js').chart} chart
 */

/**
 * Orders Report charts filter.
 *
 * @filter woocommerce_admin_orders_report_charts
 * @param {Array.<chart>} charts Report charts.
 */
export const charts = applyFilters( ORDERS_REPORT_CHARTS_FILTER, [
	{
		key: 'orders_count',
		label: __( 'Orders', 'woocommerce' ),
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net sales', 'woocommerce' ),
		order: 'desc',
		orderby: 'net_total',
		type: 'currency',
	},
	{
		key: 'avg_order_value',
		label: __( 'Average order value', 'woocommerce' ),
		type: 'currency',
	},
	{
		key: 'avg_items_per_order',
		label: __( 'Average items per order', 'woocommerce' ),
		order: 'desc',
		orderby: 'num_items_sold',
		type: 'average',
	},
] );

/**
 * @typedef {import('../index.js').filter} filter
 */

/**
 * Orders Report Filters.
 *
 * @filter woocommerce_admin_orders_report_filters
 * @param {Array.<filter>} filters Report filters.
 */
export const filters = applyFilters( ORDERS_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All orders', 'woocommerce' ), value: 'all' },
			{
				label: __( 'Advanced filters', 'woocommerce' ),
				value: 'advanced',
			},
		],
	},
] );

/*eslint-disable max-len*/

/**
 * Orders Report Advanced Filters.
 *
 * @filter woocommerce_admin_orders_report_advanced_filters
 * @param {Object} advancedFilters         Report Advanced Filters.
 * @param {string} advancedFilters.title   Interpolated component string for Advanced Filters title.
 * @param {Object} advancedFilters.filters An object specifying a report's Advanced Filters.
 */
export const advancedFilters = applyFilters(
	ORDERS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		title: _x(
			'Orders match <select/> filters',
			'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce'
		),
		filters: {
			status: {
				labels: {
					add: __( 'Order Status', 'woocommerce' ),
					remove: __( 'Remove order status filter', 'woocommerce' ),
					rule: __(
						'Select an order status filter match',
						'woocommerce'
					),
					/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Order Status</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select an order status', 'woocommerce' ),
				},
				rules: [
					{
						value: 'is',
						/* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Is', 'order status', 'woocommerce' ),
					},
					{
						value: 'is_not',
						/* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Is Not', 'order status', 'woocommerce' ),
					},
				],
				input: {
					component: 'SelectControl',
					options: Object.keys( ORDER_STATUSES ).map( ( key ) => ( {
						value: key,
						label: ORDER_STATUSES[ key ],
					} ) ),
				},
			},
			product: {
				labels: {
					add: __( 'Products', 'woocommerce' ),
					placeholder: __( 'Search products', 'woocommerce' ),
					remove: __( 'Remove products filter', 'woocommerce' ),
					rule: __( 'Select a product filter match', 'woocommerce' ),
					/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Product</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select products', 'woocommerce' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Includes', 'products', 'woocommerce' ),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Excludes', 'products', 'woocommerce' ),
					},
				],
				input: {
					component: 'Search',
					type: 'products',
					getLabels: getProductLabels,
				},
			},
			variation: {
				labels: {
					add: __( 'Variations', 'woocommerce' ),
					placeholder: __( 'Search variations', 'woocommerce' ),
					remove: __( 'Remove variations filter', 'woocommerce' ),
					rule: __(
						'Select a variation filter match',
						'woocommerce'
					),
					/* translators: A sentence describing a Variation filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Variation</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select variation', 'woocommerce' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Includes', 'variations', 'woocommerce' ),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Excludes', 'variations', 'woocommerce' ),
					},
				],
				input: {
					component: 'Search',
					type: 'variations',
					getLabels: getVariationLabels,
				},
			},
			coupon: {
				labels: {
					add: __( 'Coupon Codes', 'woocommerce' ),
					placeholder: __( 'Search coupons', 'woocommerce' ),
					remove: __( 'Remove coupon filter', 'woocommerce' ),
					rule: __( 'Select a coupon filter match', 'woocommerce' ),
					/* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Coupon code</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select coupon codes', 'woocommerce' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Includes', 'coupon code', 'woocommerce' ),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Excludes', 'coupon code', 'woocommerce' ),
					},
				],
				input: {
					component: 'Search',
					type: 'coupons',
					getLabels: getCouponLabels,
				},
			},
			customer_type: {
				labels: {
					add: __( 'Customer type', 'woocommerce' ),
					remove: __( 'Remove customer filter', 'woocommerce' ),
					rule: __( 'Select a customer filter match', 'woocommerce' ),
					/* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Customer is</title> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select a customer type', 'woocommerce' ),
				},
				input: {
					component: 'SelectControl',
					options: [
						{
							value: 'new',
							label: __( 'New', 'woocommerce' ),
						},
						{
							value: 'returning',
							label: __( 'Returning', 'woocommerce' ),
						},
					],
					defaultOption: 'new',
				},
			},
			refunds: {
				labels: {
					add: __( 'Refunds', 'woocommerce' ),
					remove: __( 'Remove refunds filter', 'woocommerce' ),
					rule: __( 'Select a refund filter match', 'woocommerce' ),
					title: __(
						'<title>Refunds</title> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select a refund type', 'woocommerce' ),
				},
				input: {
					component: 'SelectControl',
					options: [
						{
							value: 'all',
							label: __( 'All', 'woocommerce' ),
						},
						{
							value: 'partial',
							label: __( 'Partially refunded', 'woocommerce' ),
						},
						{
							value: 'full',
							label: __( 'Fully refunded', 'woocommerce' ),
						},
						{
							value: 'none',
							label: __( 'None', 'woocommerce' ),
						},
					],
					defaultOption: 'all',
				},
			},
			tax_rate: {
				labels: {
					add: __( 'Tax Rates', 'woocommerce' ),
					placeholder: __( 'Search tax rates', 'woocommerce' ),
					remove: __( 'Remove tax rate filter', 'woocommerce' ),
					rule: __( 'Select a tax rate filter match', 'woocommerce' ),
					/* translators: A sentence describing a tax rate filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Tax Rate</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select tax rates', 'woocommerce' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Includes', 'tax rate', 'woocommerce' ),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Excludes', 'tax rate', 'woocommerce' ),
					},
				],
				input: {
					component: 'Search',
					type: 'taxes',
					getLabels: getTaxRateLabels,
				},
			},
			attribute: {
				allowMultiple: true,
				labels: {
					add: __( 'Attribute', 'woocommerce' ),
					placeholder: __( 'Search attributes', 'woocommerce' ),
					remove: __( 'Remove attribute filter', 'woocommerce' ),
					rule: __(
						'Select a product attribute filter match',
						'woocommerce'
					),
					/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Attribute</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select attributes', 'woocommerce' ),
				},
				rules: [
					{
						value: 'is',
						/* translators: Sentence fragment, logical, "Is" refers to searching for products matching a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Is', 'product attribute', 'woocommerce' ),
					},
					{
						value: 'is_not',
						/* translators: Sentence fragment, logical, "Is Not" refers to searching for products that don\'t match a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Is Not',
							'product attribute',
							'woocommerce'
						),
					},
				],
				input: {
					component: 'ProductAttribute',
				},
			},
			attribution: {
				labels: {
					add: __( 'Order attribution', 'woocommerce' ),
					remove: __(
						'Remove order attribution filter',
						'woocommerce'
					),
					rule: __(
						'Select an order attribution filter match',
						'woocommerce'
					),
					/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'<title>Order attribution</title> <rule/> <filter/>',
						'woocommerce'
					),
					filter: __( 'Select an order attribution', 'woocommerce' ),
				},
				rules: [
					{
						value: 'is',
						/* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Is', 'order attribution', 'woocommerce' ),
					},
					{
						value: 'is_not',
						/* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Is Not',
							'order attribution',
							'woocommerce'
						),
					},
				],
				input: {
					component: 'SelectControl',
					getOptions: async () => {
						// TODO: call API to get list of options.
						return [
							{
								value: 'direct',
								label: 'Direct',
							},
						];
					},
				},
			},
		},
	}
);
/*eslint-enable max-len*/
