/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	applyFilters,
	addAction,
	removeAction,
	didFilter,
} from '@wordpress/hooks';
import { lazy, useEffect, useState } from '@wordpress/element';

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

const getReports = () => {
	const reports = [
		{
			report: 'revenue',
			title: __( 'Revenue', 'woocommerce' ),
			component: RevenueReport,
			navArgs: {
				id: 'woocommerce-analytics-revenue',
			},
		},
		{
			report: 'products',
			title: __( 'Products', 'woocommerce' ),
			component: ProductsReport,
			navArgs: {
				id: 'woocommerce-analytics-products',
			},
		},
		{
			report: 'variations',
			title: __( 'Variations', 'woocommerce' ),
			component: VariationsReport,
			navArgs: {
				id: 'woocommerce-analytics-variations',
			},
		},
		{
			report: 'orders',
			title: __( 'Orders', 'woocommerce' ),
			component: OrdersReport,
			navArgs: {
				id: 'woocommerce-analytics-orders',
			},
		},
		{
			report: 'categories',
			title: __( 'Categories', 'woocommerce' ),
			component: CategoriesReport,
			navArgs: {
				id: 'woocommerce-analytics-categories',
			},
		},
		{
			report: 'coupons',
			title: __( 'Coupons', 'woocommerce' ),
			component: CouponsReport,
			navArgs: {
				id: 'woocommerce-analytics-coupons',
			},
		},
		{
			report: 'taxes',
			title: __( 'Taxes', 'woocommerce' ),
			component: TaxesReport,
			navArgs: {
				id: 'woocommerce-analytics-taxes',
			},
		},
		manageStock === 'yes'
			? {
					report: 'stock',
					title: __( 'Stock', 'woocommerce' ),
					component: StockReport,
					navArgs: {
						id: 'woocommerce-analytics-stock',
					},
			  }
			: null,
		{
			report: 'customers',
			title: __( 'Customers', 'woocommerce' ),
			component: CustomersReport,
		},
		{
			report: 'downloads',
			title: __( 'Downloads', 'woocommerce' ),
			component: DownloadsReport,
			navArgs: {
				id: 'woocommerce-analytics-downloads',
			},
		},
	].filter( Boolean );

	/**
	 * An object defining a report page.
	 *
	 * @typedef {Object} report
	 * @property {string} report    Report slug.
	 * @property {string} title     Report title.
	 * @property {Node}   component React Component to render.
	 * @property {Object} navArgs   Arguments supplied to WooCommerce Navigation.
	 */

	/**
	 * Filter Report pages list.
	 *
	 * @filter woocommerce_admin_reports_list
	 * @param {Array.<report>} reports Report pages list.
	 */
	return applyFilters( REPORTS_FILTER, reports );
};

export function useReports() {
	const [ reports, setReports ] = useState( getReports );

	/*
	 * Handler for new reports being added after the initial filter has been run,
	 * so that if any reports are added later, they can still be rendered
	 * instead of being missed due to the race condition.
	 */
	useEffect( () => {
		const handleHookAdded = ( hookName ) => {
			if (
				hookName === REPORTS_FILTER &&
				didFilter( REPORTS_FILTER ) > 0
			) {
				setReports( getReports() );
			}
		};

		const namespace = `woocommerce/woocommerce/watch_${ REPORTS_FILTER }`;
		addAction( 'hookAdded', namespace, handleHookAdded );

		// Refresh reports to catch any hooks added between initial getReports and this effect
		setReports( getReports() );

		return () => {
			removeAction( 'hookAdded', namespace );
		};
	}, [] );

	return reports;
}
