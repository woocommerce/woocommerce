/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const charts = [
	{
		key: 'gross_revenue',
		label: __( 'Gross Revenue', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'gross_revenue',
		type: 'currency',
	},
	{
		key: 'refunds',
		label: __( 'Refunds', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'refunds',
		type: 'currency',
	},
	{
		key: 'coupons',
		label: __( 'Coupons', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'coupons',
		type: 'currency',
	},
	{
		key: 'taxes',
		label: __( 'Taxes', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'taxes',
		type: 'currency',
	},
	{
		key: 'shipping',
		label: __( 'Shipping', 'woocommerce-admin' ),
		orderby: 'shipping',
		type: 'currency',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'woocommerce-admin' ),
		orderby: 'net_revenue',
		type: 'currency',
	},
];
