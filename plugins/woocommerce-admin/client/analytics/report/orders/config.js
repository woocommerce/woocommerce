/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

const { orderStatuses } = wcSettings;

export const filters = [
	{ label: __( 'All Orders', 'wc-admin' ), value: 'all' },
	{
		label: __( 'Single Order', 'wc-admin' ),
		value: 'single',
		subFilters: [
			{
				component: 'Search',
				value: 'single_order',
				path: [ 'single' ],
			},
		],
	},
	{ label: __( 'Top Orders by Items Sold', 'wc-admin' ), value: 'top_items' },
	{ label: __( 'Top Orders by Gross Sales', 'wc-admin' ), value: 'top_sales' },
	{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
];

/*eslint-disable max-len*/
export const advancedFilterConfig = {
	title: _x(
		'Orders Match {{select /}} Filters',
		'A sentence describing filters for Orders. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ',
		'wc-admin'
	),
	filters: {
		status: {
			labels: {
				add: __( 'Order Status', 'wc-admin' ),
				remove: __( 'Remove order status filter', 'wc-admin' ),
				rule: __( 'Select an order status filter match', 'wc-admin' ),
				/* translators: A sentence describing an Order Status filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( 'Order Status {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select an order status', 'wc-admin' ),
			},
			rules: [
				{
					value: 'is',
					/* translators: Sentence fragment, logical, "Is" refers to searching for orders matching a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Is', 'order status', 'wc-admin' ),
				},
				{
					value: 'is_not',
					/* translators: Sentence fragment, logical, "Is Not" refers to searching for orders that don\'t match a chosen order status. Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Is Not', 'order status', 'wc-admin' ),
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
				add: __( 'Products', 'wc-admin' ),
				placeholder: __( 'Search products', 'wc-admin' ),
				remove: __( 'Remove products filter', 'wc-admin' ),
				rule: __( 'Select a product filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( 'Product {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select products', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to orders including a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Includes', 'products', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given product(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Excludes', 'products', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
					id: product.id,
					label: product.name,
				} ) ),
			},
		},
		code: {
			labels: {
				add: __( 'Coupon Codes', 'wc-admin' ),
				placeholder: __( 'Search coupons', 'wc-admin' ),
				remove: __( 'Remove coupon filter', 'wc-admin' ),
				rule: __( 'Select a coupon filter match', 'wc-admin' ),
				/* translators: A sentence describing a Coupon filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( 'Coupon Code {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select coupon codes', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to orders including a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Includes', 'coupon code', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to orders excluding a given coupon code(s). Screenshot for context: https://cloudup.com/cSsUY9VeCVJ */
					label: _x( 'Excludes', 'coupon code', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'coupons',
				getLabels: getRequestByIdString( NAMESPACE + 'coupons', coupon => ( {
					id: coupon.id,
					label: coupon.code,
				} ) ),
			},
		},
		customer: {
			labels: {
				add: __( 'Customer Type', 'wc-admin' ),
				remove: __( 'Remove customer filter', 'wc-admin' ),
				rule: __( 'Select a customer filter match', 'wc-admin' ),
				/* translators: A sentence describing a Customer filter. See screen shot for context: https://cloudup.com/cSsUY9VeCVJ */
				title: __( 'Customer is {{filter /}}', 'wc-admin' ),
				filter: __( 'Select a customer type', 'wc-admin' ),
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: __( 'New', 'wc-admin' ) },
					{ value: 'returning', label: __( 'Returning', 'wc-admin' ) },
				],
				defaultOption: 'new',
			},
		},
	},
};
/*eslint-enable max-len*/
