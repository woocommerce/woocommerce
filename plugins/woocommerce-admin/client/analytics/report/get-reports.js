/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { lazy } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '../../settings';
const manageStock = getSetting( 'manageStock', 'no' );

/**
 * Internal dependencies
 */
const RevenueReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-revenue" */ './revenue' )
);
const ProductsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report-products" */ './products' )
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

import { REPORTS_FILTER } from './index';

export default () => {
	const reports = [
		{
			report: 'revenue',
			title: __( 'Revenue', 'woocommerce-admin' ),
			component: RevenueReport,
		},
		{
			report: 'products',
			title: __( 'Products', 'woocommerce-admin' ),
			component: ProductsReport,
		},
		{
			report: 'orders',
			title: __( 'Orders', 'woocommerce-admin' ),
			component: OrdersReport,
		},
		{
			report: 'categories',
			title: __( 'Categories', 'woocommerce-admin' ),
			component: CategoriesReport,
		},
		{
			report: 'coupons',
			title: __( 'Coupons', 'woocommerce-admin' ),
			component: CouponsReport,
		},
		{
			report: 'taxes',
			title: __( 'Taxes', 'woocommerce-admin' ),
			component: TaxesReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'woocommerce-admin' ),
			component: DownloadsReport,
		},
		manageStock === 'yes'
			? {
					report: 'stock',
					title: __( 'Stock', 'woocommerce-admin' ),
					component: StockReport,
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
		},
	].filter( Boolean );

	return applyFilters( REPORTS_FILTER, reports );
};
