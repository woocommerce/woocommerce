/** @format */
/**
 * Internal dependencies
 */

import { charts as ordersCharts } from 'analytics/report/orders/config';
import { charts as productsCharts } from 'analytics/report/products/config';
import { charts as revenueCharts } from 'analytics/report/revenue/config';
import { charts as categoriesCharts } from 'analytics/report/categories/config';
import { charts as couponsCharts } from 'analytics/report/coupons/config';
import { charts as taxesCharts } from 'analytics/report/taxes/config';

const allCharts = ordersCharts
	.map( d => ( { ...d, endpoint: 'orders' } ) )
	.concat(
		productsCharts.map( d => ( { ...d, endpoint: 'products' } ) ),
		revenueCharts.map( d => ( { ...d, endpoint: 'revenue' } ) ),
		categoriesCharts.map( d => ( { ...d, endpoint: 'categories' } ) ),
		couponsCharts.map( d => ( { ...d, endpoint: 'orders' } ) ),
		taxesCharts.map( d => ( { ...d, endpoint: 'taxes' } ) )
	);

// Need to remove duplicate charts, by key, from the configs
const uniqCharts = allCharts.reduce( ( a, b ) => {
	if ( a.findIndex( d => d.key === b.key ) < 0 ) {
		a.push( b );
	}
	return a;
}, [] );

// Default charts.
// TODO: Implement user-based toggling/persistence.
const defaultCharts = [ 'items_sold', 'gross_revenue' ];

export const showCharts = uniqCharts.map( d => ( {
	...d,
	show: defaultCharts.indexOf( d.key ) >= 0,
} ) );
export const getChartFromKey = key => allCharts.filter( d => d.key === key );
