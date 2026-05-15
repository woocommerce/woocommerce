import { applyFilters } from '@wordpress/hooks';
import type { ActivitySource } from '../types';

const FILTER_NAME = 'storeActivity.sources';

/**
 * Hook to get all registered activity sources.
 *
 * Plugins can register sources via WordPress filters:
 *
 * @example
 * ```tsx
 * addFilter( 'storeActivity.sources', 'woocommerce', ( sources ) => [
 *   ...sources,
 *   {
 *     id: 'woocommerce/orders',
 *     useActivity: useOrdersActivity,
 *   },
 * ] );
 * ```
 *
 * @return {ActivitySource[]} Array of registered activity sources
 */
export function useActivitySources(): ActivitySource[] {
	const defaultSources: ActivitySource[] = [];

	// Apply filter to allow plugins to register sources.
	return applyFilters( FILTER_NAME, defaultSources ) as ActivitySource[];
}
