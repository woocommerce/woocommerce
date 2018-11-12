/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const charts = [
	{
		key: 'gross_revenue',
		label: __( 'Gross Revenue', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'refunds',
		label: __( 'Refunds', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'coupons',
		label: __( 'Coupons', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'taxes',
		label: __( 'Taxes', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'shipping',
		label: __( 'Shipping', 'wc-admin' ),
		type: 'currency',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'wc-admin' ),
		type: 'currency',
	},
];
