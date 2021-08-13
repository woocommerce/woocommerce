/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { ORDER_STATUSES } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import {
	getCouponLabels,
	getProductLabels,
	getTaxRateLabels,
	getVariationLabels,
} from '../../../lib/async-requests';

const ORDERS_REPORT_CHARTS_FILTER = 'woocommerce_admin_orders_report_charts';
const ORDERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_orders_report_filters';
const ORDERS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_orders_report_advanced_filters';

export const charts = applyFilters( ORDERS_REPORT_CHARTS_FILTER, [
	{
		key: 'orders_count',
		label: __( 'Orders', 'woocommerce-admin' ),
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net sales', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'net_total',
		type: 'currency',
	},
	{
		key: 'avg_order_value',
		label: __( 'Average order value', 'woocommerce-admin' ),
		type: 'currency',
	},
	{
		key: 'avg_items_per_order',
		label: __( 'Average items per order', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'num_items_sold',
		type: 'average',
	},
] );

export const filters = applyFilters( ORDERS_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All orders', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Advanced filters', 'woocommerce-admin' ),
				value: 'advanced',
			},
		],
	},
] );

/*eslint-disable max-len*/
export const advancedFilters = applyFilters(
	ORDERS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		title: _x(
			'Orders match {{select /}} filters',
			'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
			'woocommerce-admin'
		),
		filters: {
			status: {
				labels: {
					add: __( 'Order Status', 'woocommerce-admin' ),
					remove: __(
						'Remove order status filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select an order status filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Order Status{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select an order status', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'is',
						/* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x( 'Is', 'order status', 'woocommerce-admin' ),
					},
					{
						value: 'is_not',
						/* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Is Not',
							'order status',
							'woocommerce-admin'
						),
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
					add: __( 'Products', 'woocommerce-admin' ),
					placeholder: __( 'Search products', 'woocommerce-admin' ),
					remove: __( 'Remove products filter', 'woocommerce-admin' ),
					rule: __(
						'Select a product filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Product{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select products', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Includes',
							'products',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Excludes',
							'products',
							'woocommerce-admin'
						),
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
					add: __( 'Variations', 'woocommerce-admin' ),
					placeholder: __( 'Search variations', 'woocommerce-admin' ),
					remove: __(
						'Remove variations filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select a variation filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Variation filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Variation{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select variation', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Includes',
							'variations',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given variation(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Excludes',
							'variations',
							'woocommerce-admin'
						),
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
					add: __( 'Coupon Codes', 'woocommerce-admin' ),
					placeholder: __( 'Search coupons', 'woocommerce-admin' ),
					remove: __( 'Remove coupon filter', 'woocommerce-admin' ),
					rule: __(
						'Select a coupon filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Coupon code{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select coupon codes', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Includes',
							'coupon code',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Excludes',
							'coupon code',
							'woocommerce-admin'
						),
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
					add: __( 'Customer type', 'woocommerce-admin' ),
					remove: __( 'Remove customer filter', 'woocommerce-admin' ),
					rule: __(
						'Select a customer filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Customer is{{/title}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select a customer type', 'woocommerce-admin' ),
				},
				input: {
					component: 'SelectControl',
					options: [
						{
							value: 'new',
							label: __( 'New', 'woocommerce-admin' ),
						},
						{
							value: 'returning',
							label: __( 'Returning', 'woocommerce-admin' ),
						},
					],
					defaultOption: 'new',
				},
			},
			refunds: {
				labels: {
					add: __( 'Refunds', 'woocommerce-admin' ),
					remove: __( 'Remove refunds filter', 'woocommerce-admin' ),
					rule: __(
						'Select a refund filter match',
						'woocommerce-admin'
					),
					title: __(
						'{{title}}Refunds{{/title}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select a refund type', 'woocommerce-admin' ),
				},
				input: {
					component: 'SelectControl',
					options: [
						{
							value: 'all',
							label: __( 'All', 'woocommerce-admin' ),
						},
						{
							value: 'partial',
							label: __(
								'Partially refunded',
								'woocommerce-admin'
							),
						},
						{
							value: 'full',
							label: __( 'Fully refunded', 'woocommerce-admin' ),
						},
						{
							value: 'none',
							label: __( 'None', 'woocommerce-admin' ),
						},
					],
					defaultOption: 'all',
				},
			},
			tax_rate: {
				labels: {
					add: __( 'Tax Rates', 'woocommerce-admin' ),
					placeholder: __( 'Search tax rates', 'woocommerce-admin' ),
					remove: __( 'Remove tax rate filter', 'woocommerce-admin' ),
					rule: __(
						'Select a tax rate filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a tax rate filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Tax Rate{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select tax rates', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to orders including a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Includes',
							'tax rate',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given tax rate(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Excludes',
							'tax rate',
							'woocommerce-admin'
						),
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
					add: __( 'Attribute', 'woocommerce-admin' ),
					placeholder: __( 'Search attributes', 'woocommerce-admin' ),
					remove: __(
						'Remove attribute filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select a product attribute filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
					title: __(
						'{{title}}Attribute{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select attributes', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'is',
						/* translators: Sentence fragment, logical, "Is" refers to searching for products matching a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Is',
							'product attribute',
							'woocommerce-admin'
						),
					},
					{
						value: 'is_not',
						/* translators: Sentence fragment, logical, "Is Not" refers to searching for products that don\'t match a chosen attribute. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
						label: _x(
							'Is Not',
							'product attribute',
							'woocommerce-admin'
						),
					},
				],
				input: {
					component: 'ProductAttribute',
				},
			},
		},
	}
);
/*eslint-enable max-len*/
