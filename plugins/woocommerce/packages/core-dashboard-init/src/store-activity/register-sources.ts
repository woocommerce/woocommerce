import { addFilter } from '@wordpress/hooks';
import { useOrdersActivity } from './orders';
import { useReviewsActivity } from './reviews';

type ActivitySource = {
	id: string;
	useActivity: () => unknown;
};

/**
 * Registers WooCommerce-core activity sources for the Store Activity widget.
 *
 * Sources registered:
 * - `woocommerce/orders`: recent orders, newest first
 * - `woocommerce/reviews`: recent product reviews, newest first
 */
export function registerStoreActivitySources(): void {
	addFilter(
		'storeActivity.sources',
		'woocommerce/core-dashboard-init',
		( sources: ActivitySource[] ) => [
			...sources,
			{
				id: 'woocommerce/orders',
				useActivity: useOrdersActivity,
			},
			{
				id: 'woocommerce/reviews',
				useActivity: useReviewsActivity,
			},
		]
	);
}
