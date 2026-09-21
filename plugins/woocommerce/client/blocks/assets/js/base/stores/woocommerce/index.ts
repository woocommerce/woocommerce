/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { catalogState } from './catalog';
import { scopeState, bindState } from './scope';
import { cartActionsState, cartActions, bindCartState } from './cart-actions';
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
	CartActionsState,
	CartActionsActions,
	AddCartItemPayload,
	AddCartItemOptions,
	AddCartItemError,
	AddCartItemOutcome,
	OptimisticCartItem,
	SelectedAttributes,
	WooCommerceConfig,
} from './types';

// The acknowledgement string the other stores in this folder pass to
// `store()`. Reusing it here is what lets a consumer that already knows it
// call `store( 'woocommerce', {}, { lock } )` and still resolve this store.
const storeConsent =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// `scopeState.productScope` is a getter, so it is merged with
// `Object.defineProperties` rather than object-spread: spreading an accessor
// property invokes it immediately and copies its one-time result as a plain
// value, silently turning the live envelope into a frozen snapshot. The
// target is cast to the assembled state type up front because
// `Object.defineProperties` returns its target's own type, not the type of
// the properties it defines.
const initialState = Object.defineProperties(
	{ ...catalogState, ...cartActionsState } as WooCommerceStore[ 'state' ],
	Object.getOwnPropertyDescriptors( scopeState )
);

/**
 * The unified `woocommerce` Interactivity API store.
 *
 * This is the module's one `store()` registration. Its implementation is
 * split by concern across sibling files — this file assembles them into a
 * single call — the catalog layer (`catalog.ts`), the product scope envelope
 * (`scope.ts`), and the cart plane (`cart-actions.ts`).
 */
const { state, actions } = store< WooCommerceStore >(
	'woocommerce',
	{
		state: initialState,
		actions: cartActions,
	},
	{ lock: storeConsent }
);

// `scope.ts` calls `cart-actions.ts`'s `findCartLine` directly for
// `cartItem`, and `cart-actions.ts` reads and writes `scope.ts`'s
// `productScopes` / `productScope` for `addCartItem()`'s draft form —
// hence binding both to the same returned state reference, immediately
// after the one registration above.
bindState( state );
// `cart-actions.ts`'s own state type additionally reads `restUrl` / `nonce`
// / `errorMessages`, seeded by PHP (`BlocksSharedState.php`) but not part of
// `WooCommerceStore`'s published state type — hence the cast, widening the
// assembled type to the fuller shape it reads at runtime.
bindCartState(
	state as unknown as Parameters< typeof bindCartState >[ 0 ],
	actions
);
