import {
	registerWooCommerceCustomerEntity,
	registerWooCommerceOrderEntity,
	registerWooCommerceProductReviewEntity,
} from './data';
import { registerStoreActivitySources } from './store-activity';

/**
 * Entry point invoked by the dashboard boot. Loaded as a static dependency
 * from Loader.php's `dashboard-wp-admin_boot_dependencies` filter, so its
 * top-level code runs during module evaluation — before the store-activity
 * widget renders and reads the filter.
 *
 * Order matters: entities must be registered before any source's
 * `useActivity` hook calls `getEntityRecords( ... )`.
 */
registerWooCommerceOrderEntity();
registerWooCommerceProductReviewEntity();
registerWooCommerceCustomerEntity();
registerStoreActivitySources();

/**
 * No-op `init()` kept for forward compatibility with wp-build's formal init
 * module contract (`wpPlugin.pages[].init`). The actual work happens above
 * at module load.
 */
export async function init(): Promise< void > {
	// Intentionally empty.
}
