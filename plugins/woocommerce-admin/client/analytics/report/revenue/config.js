/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const REVENUE_REPORT_CHART_FILTER = 'woocommerce_admin_revenue_report_chart_filter';

export const charts = applyFilters( REVENUE_REPORT_CHART_FILTER, [
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
] );
