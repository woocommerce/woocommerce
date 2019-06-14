/** @format */
/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */

import { charts as ordersCharts } from 'analytics/report/orders/config';
import { charts as productsCharts } from 'analytics/report/products/config';
import { charts as revenueCharts } from 'analytics/report/revenue/config';
import { charts as categoriesCharts } from 'analytics/report/categories/config';
import { charts as couponsCharts } from 'analytics/report/coupons/config';
import { charts as taxesCharts } from 'analytics/report/taxes/config';
import { charts as downloadsCharts } from 'analytics/report/downloads/config';

const DASHBOARD_CHARTS_FILTER = 'woocommerce_admin_dashboard_charts_filter';

const allCharts = applyFilters(
	DASHBOARD_CHARTS_FILTER,
	revenueCharts
		.map( d => ( { ...d, endpoint: 'revenue' } ) )
		.concat(
			ordersCharts.map( d => ( { ...d, endpoint: 'orders' } ) ),
			productsCharts.map( d => ( { ...d, endpoint: 'products' } ) ),
			categoriesCharts.map( d => ( { ...d, endpoint: 'categories' } ) ),
			couponsCharts.map( d => ( { ...d, endpoint: 'orders' } ) ),
			taxesCharts.map( d => ( { ...d, endpoint: 'taxes' } ) ),
			downloadsCharts.map( d => ( { ...d, endpoint: 'downloads' } ) )
		)
);

// Need to remove duplicate charts, by key, from the configs
export const uniqCharts = allCharts.reduce( ( a, b ) => {
	if ( a.findIndex( d => d.key === b.key ) < 0 ) {
		a.push( b );
	}
	return a;
}, [] );

export const getChartFromKey = key => allCharts.filter( d => d.key === key );
