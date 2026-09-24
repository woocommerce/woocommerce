/**
 * Internal dependencies
 */
import type { CatalogState } from './catalog';

export type { CatalogState } from './catalog';

/**
 * The unified `woocommerce` Interactivity API store's public TypeScript
 * surface, assembled from the catalog layer. The `woocommerce/cart` state
 * and actions register separately, from `cart.ts`.
 */
export type WooCommerceStore = {
	state: CatalogState;
};
