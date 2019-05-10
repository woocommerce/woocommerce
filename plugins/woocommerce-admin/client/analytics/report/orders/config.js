/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCouponLabels, getProductLabels } from 'lib/async-requests';

const { orderStatuses } = wcSettings;

export const charts = [
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'woocommerce-admin' ),
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'net_total',
		type: 'currency',
	},
	{
		key: 'avg_order_value',
		label: __( 'Average Order Value', 'woocommerce-admin' ),
		type: 'currency',
	},
	{
		key: 'avg_items_per_order',
		label: __( 'Average Items Per Order', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'num_items_sold',
		type: 'average',
	},
];

export const filters = [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Orders', 'woocommerce-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'woocommerce-admin' ), value: 'advanced' },
		],
	},
];

/*eslint-disable max-len*/
export const advancedFilters = {
	title: _x(
		'Orders Match {{select /}} Filters',
		'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
		'woocommerce-admin'
	),
	filters: {
		status: {
			labels: {
				add: __( 'Order Status', 'woocommerce-admin' ),
				remove: __( 'Remove order status filter', 'woocommerce-admin' ),
				rule: __( 'Select an order status filter match', 'woocommerce-admin' ),
				/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( '{{title}}Order Status{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin' ),
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
					label: _x( 'Is Not', 'order status', 'woocommerce-admin' ),
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( orderStatuses ).map( key => ( {
					value: key,
					label: orderStatuses[ key ],
				} ) ),
			},
		},
		product: {
			labels: {
				add: __( 'Products', 'woocommerce-admin' ),
				placeholder: __( 'Search products', 'woocommerce-admin' ),
				remove: __( 'Remove products filter', 'woocommerce-admin' ),
				rule: __( 'Select a product filter match', 'woocommerce-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( '{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin' ),
				filter: __( 'Select products', 'woocommerce-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Includes', 'products', 'woocommerce-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Excludes', 'products', 'woocommerce-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: getProductLabels,
			},
		},
		coupon: {
			labels: {
				add: __( 'Coupon Codes', 'woocommerce-admin' ),
				placeholder: __( 'Search coupons', 'woocommerce-admin' ),
				remove: __( 'Remove coupon filter', 'woocommerce-admin' ),
				rule: __( 'Select a coupon filter match', 'woocommerce-admin' ),
				/* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( '{{title}}Coupon Code{{/title}} {{rule /}} {{filter /}}', 'woocommerce-admin' ),
				filter: __( 'Select coupon codes', 'woocommerce-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Includes', 'coupon code', 'woocommerce-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Excludes', 'coupon code', 'woocommerce-admin' ),
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
				add: __( 'Customer Type', 'woocommerce-admin' ),
				remove: __( 'Remove customer filter', 'woocommerce-admin' ),
				rule: __( 'Select a customer filter match', 'woocommerce-admin' ),
				/* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( '{{title}}Customer is{{/title}} {{filter /}}', 'woocommerce-admin' ),
				filter: __( 'Select a customer type', 'woocommerce-admin' ),
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: __( 'New', 'woocommerce-admin' ) },
					{ value: 'returning', label: __( 'Returning', 'woocommerce-admin' ) },
				],
				defaultOption: 'new',
			},
		},
		refunds: {
			labels: {
				add: __( 'Refunds', 'woocommerce-admin' ),
				remove: __( 'Remove refunds filter', 'woocommerce-admin' ),
				rule: __( 'Select a refund filter match', 'woocommerce-admin' ),
				title: __( '{{title}}Refunds{{/title}} {{filter /}}', 'woocommerce-admin' ),
				filter: __( 'Select a refund type', 'woocommerce-admin' ),
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'all', label: __( 'All', 'woocommerce-admin' ) },
					{ value: 'partial', label: __( 'Partially refunded', 'woocommerce-admin' ) },
					{ value: 'full', label: __( 'Fully refunded', 'woocommerce-admin' ) },
					{ value: 'none', label: __( 'None', 'woocommerce-admin' ) },
				],
				defaultOption: 'all',
			},
		},
	},
};
/*eslint-enable max-len*/
