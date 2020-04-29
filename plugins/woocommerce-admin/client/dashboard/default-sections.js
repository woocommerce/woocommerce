/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Spinner } from '@woocommerce/components';
const LazyDashboardCharts = lazy( () =>
	import( /* webpackChunkName: "dashboard-charts" */ './dashboard-charts' )
);
const LazyLeaderboards = lazy( () =>
	import( /* webpackChunkName: "leaderboards" */ './leaderboards' )
);
const LazyStorePerformance = lazy( () =>
	import( /* webpackChunkName: "store-performance" */ './store-performance' )
);

const DashboardCharts = ( props ) => (
	<Suspense fallback={ <Spinner /> }>
		<LazyDashboardCharts { ...props } />
	</Suspense>
);

const Leaderboards = ( props ) => (
	<Suspense fallback={ <Spinner /> }>
		<LazyLeaderboards { ...props } />
	</Suspense>
);

const StorePerformance = ( props ) => (
	<Suspense fallback={ <Spinner /> }>
		<LazyStorePerformance { ...props } />
	</Suspense>
);

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
			'orders/avg_order_value',
			'revenue/refunds',
			'revenue/gross_sales',
		],
	},
	{
		key: 'charts',
		component: DashboardCharts,
		title: __( 'Charts', 'woocommerce-admin' ),
		isVisible: true,
		icon: 'chart-bar',
		hiddenBlocks: [
			'orders_avg_order_value',
			'avg_items_per_order',
			'products_items_sold',
			'revenue_total_sales',
			'revenue_refunds',
			'coupons_amount',
			'coupons_orders_count',
			'revenue_shipping',
			'taxes_total_tax',
			'taxes_order_tax',
			'taxes_shipping_tax',
			'downloads_download_count',
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
