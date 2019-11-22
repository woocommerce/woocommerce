/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const REVENUE_REPORT_CHARTS_FILTER = 'woocommerce_admin_revenue_report_charts';
const REVENUE_REPORT_FILTERS_FILTER = 'woocommerce_admin_revenue_report_filters';
const REVENUE_REPORT_ADVANCED_FILTERS_FILTER = 'woocommerce_admin_revenue_report_advanced_filters';

export const charts = applyFilters( REVENUE_REPORT_CHARTS_FILTER, [
	{
		key: 'gross_sales',
		label: __( 'Gross Sales', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'gross_sales',
		type: 'currency',
	},
	{
		key: 'refunds',
		label: __( 'Returns', 'woocommerce-admin' ),
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
		key: 'net_revenue',
		label: __( 'Net Sales', 'woocommerce-admin' ),
		orderby: 'net_revenue',
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
		key: 'total_sales',
		label: __( 'Total Sales', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'total_sales',
		type: 'currency',
	},
] );

export const filters = applyFilters( REVENUE_REPORT_FILTERS_FILTER, [] );
export const advancedFilters = applyFilters( REVENUE_REPORT_ADVANCED_FILTERS_FILTER, {} );
