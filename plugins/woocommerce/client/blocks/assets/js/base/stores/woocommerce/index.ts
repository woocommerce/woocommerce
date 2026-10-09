/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { catalogState } from './catalog';
import type { WooCommerceStore } from './types';

export type { WooCommerceStore, CatalogState } from './types';
export type { ProductsStore, ProductsStoreState } from './products';

// The acknowledgement string the other stores in this folder pass to
// `store()`. Reusing it here is what lets a consumer that already knows it
// call `store( 'woocommerce', {}, { lock } )` and still resolve this store.
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The catalog layer of the unified `woocommerce` Interactivity API store:
 * server-seeded product and variation data. `cart.ts` registers the same
 * namespace's cart state and actions from a separate module; the
 * Interactivity API merges repeated `store()` calls for one namespace, so
 * both contribute to the same store.
 */
store< WooCommerceStore >(
	'woocommerce',
	{
		state: catalogState,
	},
	{ lock: storeConsent }
);
