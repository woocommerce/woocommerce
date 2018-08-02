/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const filters = [
	{ label: __( 'All Orders', 'wc-admin' ), value: 'all' },
	{
		label: __( 'Single Order', 'wc-admin' ),
		value: 'single',
		subFilters: [
			{
				label: __( 'Single Order', 'wc-admin' ),
				component: 'Search',
				value: 'single_order',
			},
		],
	},
	{ label: __( 'Top Orders by Items Sold', 'wc-admin' ), value: 'top_items' },
	{ label: __( 'Top Orders by Gross Sales', 'wc-admin' ), value: 'top_sales' },
	{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
];

export const filterPaths = {
	all: [],
	single: [],
	single_order: [ 'single' ],
	top_items: [],
	top_sales: [],
	advanced: [],
};

export const advancedFilterConfig = {
	status: {
		label: __( 'Order Status', 'wc-admin' ),
		addLabel: __( 'Order Status', 'wc-admin' ),
		rules: [
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is-not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'SelectControl',
			options: [
				{ value: 'pending', label: __( 'Pending', 'wc-admin' ) },
				{ value: 'processing', label: __( 'Processing', 'wc-admin' ) },
				{ value: 'on-hold', label: __( 'On Hold', 'wc-admin' ) },
				{ value: 'completed', label: __( 'Completed', 'wc-admin' ) },
				{ value: 'refunded', label: __( 'Refunded', 'wc-admin' ) },
				{ value: 'cancelled', label: __( 'Cancelled', 'wc-admin' ) },
				{ value: 'failed', label: __( 'Failed', 'wc-admin' ) },
			],
		},
	},
	product: {
		label: __( 'Product', 'wc-admin' ),
		addLabel: __( 'Products', 'wc-admin' ),
		rules: [
			{ value: 'includes', label: __( 'Includes', 'wc-admin' ) },
			{ value: 'excludes', label: __( 'Excludes', 'wc-admin' ) },
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is-not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'FormTokenField',
		},
	},
	coupon: {
		label: __( 'Coupon Code', 'wc-admin' ),
		addLabel: __( 'Coupon Codes', 'wc-admin' ),
		rules: [
			{ value: 'includes', label: __( 'Includes', 'wc-admin' ) },
			{ value: 'excludes', label: __( 'Excludes', 'wc-admin' ) },
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is-not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'FormTokenField',
		},
	},
	customer: {
		label: __( 'Customer is', 'wc-admin' ),
		addLabel: __( 'Customer Type', 'wc-admin' ),
		rules: [
			{ value: 'new', label: __( 'New', 'wc-admin' ) },
			{ value: 'returning', label: __( 'Returning', 'wc-admin' ) },
		],
	},
};
