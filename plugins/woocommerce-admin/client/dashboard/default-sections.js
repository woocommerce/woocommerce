/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import DashboardCharts from './dashboard-charts';
import Leaderboards from './leaderboards';
import StorePerformance from './store-performance';

const DEFAULT_SECTIONS_FILTER = 'woocommerce_dashboard_default_sections';

export default applyFilters( DEFAULT_SECTIONS_FILTER, [
	{
		key: 'store-performance',
		component: StorePerformance,
		title: __( 'Performance', 'woocommerce-admin' ),
		isVisible: true,
		icon: 'arrow-right-alt',
		hiddenBlocks: [
			'coupons/amount',
			'coupons/orders_count',
			'downloads/download_count',
			'taxes/order_tax',
			'taxes/total_tax',
			'taxes/shipping_tax',
			'revenue/shipping',
		],
	},
	{
		key: 'charts',
		component: DashboardCharts,
		title: __( 'Charts', 'woocommerce-admin' ),
		isVisible: true,
		icon: 'chart-bar',
		hiddenBlocks: [
			'avg_order_value',
			'avg_items_per_order',
			'items_sold',
			'total_sales',
			'refunds',
			'coupons',
			'taxes',
			'shipping',
			'amount',
			'total_tax',
			'order_tax',
			'shipping_tax',
			'download_count',
		],
	},
	{
		key: 'leaderboards',
		component: Leaderboards,
		title: __( 'Leaderboards', 'woocommerce-admin' ),
		isVisible: true,
		icon: 'editor-ol',
		hiddenBlocks: [ 'coupons', 'customers' ],
	},
] );
