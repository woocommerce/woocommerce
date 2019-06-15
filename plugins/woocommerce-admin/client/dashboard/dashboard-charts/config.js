/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */

import { charts as ordersCharts } from 'analytics/report/orders/config';
import { charts as productsCharts } from 'analytics/report/products/config';
import { charts as revenueCharts } from 'analytics/report/revenue/config';
import { charts as couponsCharts } from 'analytics/report/coupons/config';
import { charts as taxesCharts } from 'analytics/report/taxes/config';
import { charts as downloadsCharts } from 'analytics/report/downloads/config';

const DASHBOARD_CHARTS_FILTER = 'woocommerce_admin_dashboard_charts_filter';

const charts = {
	revenue: revenueCharts,
	orders: ordersCharts,
	products: productsCharts,
	coupons: couponsCharts,
	taxes: taxesCharts,
	downloads: downloadsCharts,
};

const defaultCharts = [
	{
		label: __( 'Gross Revenue', 'woocommerce-admin' ),
		report: 'revenue',
		key: 'gross_revenue',
	},
	{
		label: __( 'Net Revenue', 'woocommerce-admin' ),
		report: 'revenue',
		key: 'net_revenue',
	},
	{
		label: __( 'Orders', 'woocommerce-admin' ),
		report: 'orders',
		key: 'orders_count',
	},
	{
		label: __( 'Average Order Value', 'woocommerce-admin' ),
		report: 'orders',
		key: 'avg_order_value',
	},
	{
		label: __( 'Items Sold', 'woocommerce-admin' ),
		report: 'products',
		key: 'items_sold',
	},
	{
		label: __( 'Refunds', 'woocommerce-admin' ),
		report: 'revenue',
		key: 'refunds',
	},
	{
		label: __( 'Discounted Orders', 'woocommerce-admin' ),
		report: 'coupons',
		key: 'orders_count',
	},
	{
		label: __( 'Gross discounted', 'woocommerce-admin' ),
		report: 'coupons',
		key: 'amount',
	},
	{
		label: __( 'Total Tax', 'woocommerce-admin' ),
		report: 'taxes',
		key: 'total_tax',
	},
	{
		label: __( 'Order Tax', 'woocommerce-admin' ),
		report: 'taxes',
		key: 'order_tax',
	},
	{
		label: __( 'Shipping Tax', 'woocommerce-admin' ),
		report: 'taxes',
		key: 'shipping_tax',
	},
	{
		label: __( 'Shipping', 'woocommerce-admin' ),
		report: 'revenue',
		key: 'shipping',
	},
	{
		label: __( 'Downloads', 'woocommerce-admin' ),
		report: 'downloads',
		key: 'download_count',
	},
];

export const uniqCharts = applyFilters(
	DASHBOARD_CHARTS_FILTER,
	defaultCharts.map( chartDef => ( {
		...charts[ chartDef.report ].find( chart => chart.key === chartDef.key ),
		label: chartDef.label,
		endpoint: chartDef.report,
	} ) )
);
