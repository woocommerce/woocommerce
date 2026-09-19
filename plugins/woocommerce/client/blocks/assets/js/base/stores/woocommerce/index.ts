/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { catalogState } from './catalog';
import type { WooCommerceStore } from './types';

export type {
	WooCommerceStore,
	CatalogState,
	TemplateVariationAttribute,
} from './types';

// The acknowledgement string the other stores in this folder pass to
// `store()`. Reusing it here is what lets a consumer that already knows it
// call `store( 'woocommerce', {}, { lock } )` and still resolve this store.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The unified `woocommerce` Interactivity API store.
 *
 * This is the module's one `store()` registration. Its implementation is
 * split by concern across sibling files — this file assembles them into a
 * single call — starting with the catalog layer (`catalog.ts`).
 */
store< WooCommerceStore >(
	'woocommerce',
	{
		state: {
			...catalogState,
		},
	},
	{ lock: universalLock }
);
