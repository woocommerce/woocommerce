/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { catalogState } from './catalog';
import { scopeState, bindState } from './scope';
import type { WooCommerceStore } from './types';

export type {
	WooCommerceStore,
	CatalogState,
	TemplateVariationAttribute,
	ScopeState,
	ProductScopeContext,
	ProductScopeEnvelope,
	ProductScopeRecord,
	ProductScopesState,
	DraftCartItem,
	DraftCartItemRecord,
} from './types';
export type { ProductsStore, ProductsStoreState } from './products';

// The acknowledgement string the other stores in this folder pass to
// `store()`. Reusing it here is what lets a consumer that already knows it
// call `store( 'woocommerce', {}, { lock } )` and still resolve this store.
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// `Object.defineProperties` preserves `scopeState.productScope`'s getter; a
// plain spread would invoke it once and copy a frozen snapshot instead.
const initialState = Object.defineProperties(
	{ ...catalogState } as WooCommerceStore[ 'state' ],
	Object.getOwnPropertyDescriptors( scopeState )
);

/**
 * The unified `woocommerce` Interactivity API store.
 *
 * This is the module's one `store()` registration. Its implementation is
 * split by concern across sibling files — this file assembles them into a
 * single call — the catalog layer (`catalog.ts`) and the product scope
 * envelope (`scope.ts`). The cart plane registers separately, from
 * `cart.ts`, which is its own script module.
 */
const { state } = store< WooCommerceStore >(
	'woocommerce',
	{
		state: initialState,
	},
	{ lock: storeConsent }
);

// `scope.ts` reads `cart.ts`'s `findItemInCart` when it's registered; the
// cast widens the type to the fuller shape `scope.ts` may see at runtime.
bindState( state as unknown as Parameters< typeof bindState >[ 0 ] );
