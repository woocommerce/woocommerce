/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
/**
 * List of homepage stats enabled by default
 *
 * @filter woocommerce_admin_homepage_default_stats
 * @example
 * addFilter(
 *	'woocommerce_admin_homepage_default_stats',
 *	'plugin-domain',
 *	( defaultStats ) =>
 *		defaultStats.filter( ( stat ) => stat !== 'jetpack/stats/views' )
 *);
 */
export const DEFAULT_STATS = applyFilters(
	'woocommerce_admin_homepage_default_stats',
	[
		'revenue/total_sales',
		'revenue/net_revenue',
		'orders/orders_count',
		'products/items_sold',
		'jetpack/stats/visitors',
		'jetpack/stats/views',
	]
);

export const DEFAULT_HIDDEN_STATS = [
	'revenue/net_revenue',
	'products/items_sold',
];
