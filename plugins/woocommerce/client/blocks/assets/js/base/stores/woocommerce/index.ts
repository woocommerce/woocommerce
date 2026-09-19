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

// The acknowledgement string the other stores in this folder pass to
// `store()`. Reusing it here is what lets a consumer that already knows it
// call `store( 'woocommerce', {}, { lock } )` and still resolve this store.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// `scopeState.productScope` is a getter, so it is merged with
// `Object.defineProperties` rather than object-spread: spreading an accessor
// property invokes it immediately and copies its one-time result as a plain
// value, silently turning the live envelope into a frozen snapshot. The
// target is cast to the assembled state type up front because
// `Object.defineProperties` returns its target's own type, not the type of
// the properties it defines.
const initialState = Object.defineProperties(
	{ ...catalogState } as WooCommerceStore[ 'state' ],
	Object.getOwnPropertyDescriptors( scopeState )
);

/**
 * The unified `woocommerce` Interactivity API store.
 *
 * This is the module's one `store()` registration. Its implementation is
 * split by concern across sibling files — this file assembles them into a
 * single call — starting with the catalog layer (`catalog.ts`) and the
 * product scope envelope (`scope.ts`).
 */
const { state } = store< WooCommerceStore >(
	'woocommerce',
	{
		state: initialState,
	},
	{ lock: universalLock }
);

// `scope.ts` reads and writes the cart layer's `findItemInCart` for
// `cartItem` even though the cart layer (`cart.ts`) is not part of
// `WooCommerceStore` yet (it registers onto the same `woocommerce` store
// through its own separate `store()` calls until T9 folds it into this
// module) — hence the cast, widening today's assembled type to the fuller
// shape `scope.ts` reads at runtime.
bindState( state as unknown as Parameters< typeof bindState >[ 0 ] );
