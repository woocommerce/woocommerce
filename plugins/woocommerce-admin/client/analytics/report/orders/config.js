/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getProductLabelsById } from 'analytics/report/products/config';

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

export const advancedFilterConfig = {
	status: {
		labels: {
			add: __( 'Order Status', 'wc-admin' ),
			remove: __( 'Remove order status filter', 'wc-admin' ),
			rule: __( 'Select an order status filter match', 'wc-admin' ),
			title: __( 'Order Status', 'wc-admin' ),
		},
		rules: [
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is_not', label: __( 'Is Not', 'wc-admin' ) },
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
	product_id: {
		labels: {
			add: __( 'Products', 'wc-admin' ),
			placeholder: __( 'Search products', 'wc-admin' ),
			remove: __( 'Remove products filter', 'wc-admin' ),
			rule: __( 'Select a product filter match', 'wc-admin' ),
			title: __( 'Product', 'wc-admin' ),
		},
		rules: [
			{ value: 'includes', label: __( 'Includes', 'wc-admin' ) },
			{ value: 'excludes', label: __( 'Excludes', 'wc-admin' ) },
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is_not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'Search',
			type: 'products',
			getLabels: getProductLabelsById,
		},
	},
	code: {
		labels: {
			add: __( 'Coupon Codes', 'wc-admin' ),
			placeholder: __( 'Search coupons', 'wc-admin' ),
			remove: __( 'Remove coupon filter', 'wc-admin' ),
			rule: __( 'Select a coupon filter match', 'wc-admin' ),
			title: __( 'Coupon Code', 'wc-admin' ),
		},
		rules: [
			{ value: 'includes', label: __( 'Includes', 'wc-admin' ) },
			{ value: 'excludes', label: __( 'Excludes', 'wc-admin' ) },
			{ value: 'is', label: __( 'Is', 'wc-admin' ) },
			{ value: 'is_not', label: __( 'Is Not', 'wc-admin' ) },
		],
		input: {
			component: 'Search',
			type: 'products', // For now. "coupons" autocompleter required
		},
	},
	customer: {
		labels: {
			add: __( 'Customer Type', 'wc-admin' ),
			remove: __( 'Remove customer filter', 'wc-admin' ),
			rule: __( 'Select a customer filter match', 'wc-admin' ),
			title: __( 'Customer is', 'wc-admin' ),
		},
		input: {
			component: 'SelectControl',
			options: [
				{ value: 'new', label: __( 'New', 'wc-admin' ) },
				{ value: 'returning', label: __( 'Returning', 'wc-admin' ) },
			],
		},
	},
};
