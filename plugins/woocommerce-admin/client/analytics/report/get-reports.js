/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { lazy } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
const RevenueReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-revenue" */ './revenue' )
);
const ProductsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-products" */ './products' )
);
const VariationsReport = lazy( () =>
	import(
		/* webpackChunkName: "analytics-report-variations" */ './variations'
	)
);
const OrdersReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-orders" */ './orders' )
);
const CategoriesReport = lazy( () =>
	import(
		/* webpackChunkName: "analytics-report-categories" */ './categories'
	)
);
const CouponsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-coupons" */ './coupons' )
);
const TaxesReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-taxes" */ './taxes' )
);
const DownloadsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-downloads" */ './downloads' )
);
const StockReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-stock" */ './stock' )
);
const CustomersReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-customers" */ './customers' )
);

const manageStock = getAdminSetting( 'manageStock', 'no' );
const REPORTS_FILTER = 'woocommerce_admin_reports_list';

export default () => {
	const reports = [
		{
			report: 'revenue',
			title: __( 'Revenue', 'woocommerce-admin' ),
			component: RevenueReport,
			navArgs: {
				id: 'woocommerce-analytics-revenue',
			},
		},
		{
			report: 'products',
			title: __( 'Products', 'woocommerce-admin' ),
			component: ProductsReport,
			navArgs: {
				id: 'woocommerce-analytics-products',
			},
		},
		{
			report: 'variations',
			title: __( 'Variations', 'woocommerce-admin' ),
			component: VariationsReport,
			navArgs: {
				id: 'woocommerce-analytics-variations',
			},
		},
		{
			report: 'orders',
			title: __( 'Orders', 'woocommerce-admin' ),
			component: OrdersReport,
			navArgs: {
				id: 'woocommerce-analytics-orders',
			},
		},
		{
			report: 'categories',
			title: __( 'Categories', 'woocommerce-admin' ),
			component: CategoriesReport,
			navArgs: {
				id: 'woocommerce-analytics-categories',
			},
		},
		{
			report: 'coupons',
			title: __( 'Coupons', 'woocommerce-admin' ),
			component: CouponsReport,
			navArgs: {
				id: 'woocommerce-analytics-coupons',
			},
		},
		{
			report: 'taxes',
			title: __( 'Taxes', 'woocommerce-admin' ),
			component: TaxesReport,
			navArgs: {
				id: 'woocommerce-analytics-taxes',
			},
		},
		manageStock === 'yes'
			? {
					report: 'stock',
					title: __( 'Stock', 'woocommerce-admin' ),
					component: StockReport,
					navArgs: {
						id: 'woocommerce-analytics-stock',
					},
			  }
			: null,
		{
			report: 'customers',
			title: __( 'Customers', 'woocommerce-admin' ),
			component: CustomersReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'woocommerce-admin' ),
			component: DownloadsReport,
			navArgs: {
				id: 'woocommerce-analytics-downloads',
			},
		},
	].filter( Boolean );

	return applyFilters( REPORTS_FILTER, reports );
};
