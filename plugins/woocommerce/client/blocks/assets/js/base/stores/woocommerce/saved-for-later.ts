/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

/**
 * Save-for-later Interactivity API store.
 *
 * Exposes the click actions wired to the saved-for-later UI. `moveToCart` delegates to the
 * shared `woocommerce` cart store (`actions.addCartItem`) so add-to-cart flows go through
 * the same optimistic-update + batched mutation path as the rest of the blocks.
 * `removeFromList` talks to `/wc/store/v1/saved-lists/...` and then nudges the Interactivity
 * API router to re-fetch the current page, so any server-rendered region (the Saved for Later
 * Product Collection) reflects the new state without a full reload. The React cart block
 * can trigger the same re-fetch by dispatching the `wc-blocks_save_for_later_added` window
 * event — see the listener at the bottom of this file.
 *
 * The store file lives under `base/stores/woocommerce/` so consumers can share it via the
 * `@woocommerce/stores/woocommerce/saved-for-later` alias, mirroring the cart store.
 */

type VariationAttribute = { attribute: string; value: string };
type ItemContext = {
	key: string;
	id: number;
	quantity: number;
	variation: VariationAttribute[];
	/** Product type, e.g. 'simple' or 'variation'. Used by the cart store for optimistic UI. */
	productType: string;
};

/**
 * Saved list item payload as returned by the Store API (saved-list-item schema).
 * Shape is `{ attribute, value }[]` for `variation`, which matches the cart store.
 */
export type SavedListItem = {
	key: string;
	product_id: number;
	variation_id: number;
	quantity: number;
	variation: VariationAttribute[];
	saved_at: number;
};

/**
 * Normalized shape of a saved item inside `state.items`. Keyed by product_id and matches
 * the shape seeded server-side by HandlerRegistry::seed_saved_for_later_interactivity_state.
 */
type SavedItemState = {
	key: string;
	productId: number;
	variationId: number;
	quantity: number;
	variation: Record< string, string >;
};

type SavedForLaterState = {
	items?: Record< number, SavedItemState >;
};

export type SavedForLaterStore = {
	state: SavedForLaterState;
	actions: {
		addItemToList: ( item: SavedListItem ) => void;
	};
};

// Stores are locked to prevent third-party usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Raw fetch against the Store API, using the nonce and REST URL managed by cart.ts.
 * Used by the saved-lists-only actions (save, remove) that don't go through the cart
 * mutation queue.
 */
const savedListRequest = async (
	wooState: WooCommerce[ 'state' ],
	path: string,
	method: 'POST' | 'DELETE',
	body?: Record< string, unknown >
): Promise< Response > => {
	const base = ( wooState.restUrl ?? '/wp-json/' ).replace( /\/$/, '' );
	const headers: Record< string, string > = {
		'Content-Type': 'application/json',
	};
	if ( wooState.nonce ) {
		headers.Nonce = wooState.nonce;
	}
	const init: RequestInit = {
		method,
		headers,
		credentials: 'same-origin',
	};
	if ( body ) {
		init.body = JSON.stringify( body );
	}
	return fetch( `${ base }${ path }`, init );
};

/**
 * Force the Interactivity API router to re-fetch the current page so server-rendered regions
 * (like the Saved for Later Product Collection) reflect post-mutation state without a full
 * reload. No-ops if the page has no iAPI router regions.
 *
 * De-duped: if a refresh is already in flight, mutations that land while it runs flip a
 * `pending` flag and the helper re-navigates once the current fetch settles, coalescing any
 * number of rapid mutations into at most one follow-up request.
 */
let refreshInFlight = false;
let refreshPending = false;
const refreshServerRegions = async (): Promise< void > => {
	if ( ! document.querySelector( '[data-wp-router-region]' ) ) {
		return;
	}
	if ( refreshInFlight ) {
		refreshPending = true;
		return;
	}
	refreshInFlight = true;
	try {
		do {
			refreshPending = false;
			const routerModule: typeof import('@wordpress/interactivity-router') =
				await import( '@wordpress/interactivity-router' );
			await routerModule.actions.navigate( window.location.href, {
				force: true,
				loadingAnimation: false,
				screenReaderAnnouncement: false,
				replace: true,
			} );
		} while ( refreshPending );
	} finally {
		refreshInFlight = false;
	}
};

// Two `store()` calls so actions can reference the live state proxy (same pattern as cart.ts).
const { state: sflState } = store< SavedForLaterStore >(
	'woocommerce/save-for-later',
	{},
	{ lock: universalLock }
);
// Generic omitted intentionally — the `SavedForLaterStore` type only surfaces what external
// consumers are allowed to call, which is narrower than the full set of actions this module
// registers internally (moveToCart, removeFromList).
store(
	'woocommerce/save-for-later',
	{
		actions: {
			/**
			 * Push a newly saved item into local state so consumers reading
			 * `state.items` stay consistent with the server without a full page refresh.
			 * The rendered Saved for Later grid is server-rendered, so this call does
			 * NOT materialize a new row — it only keeps the iAPI state authoritative.
			 */
			addItemToList( item: SavedListItem ) {
				const variationMap: Record< string, string > = {};
				for ( const entry of item.variation ) {
					variationMap[ entry.attribute ] = entry.value;
				}
				if ( ! sflState.items ) {
					sflState.items = {};
				}
				sflState.items[ item.product_id ] = {
					key: item.key,
					productId: item.product_id,
					variationId: item.variation_id,
					quantity: item.quantity,
					variation: variationMap,
				};
			},
			*moveToCart() {
				const { id, quantity, variation, productType } =
					getContext< ItemContext >();

				// Lazy-load the woocommerce cart store so cart.ts registers its
				// actions and nonce before we call into it.
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield actions.addCartItem( {
					id,
					quantity,
					variation,
					type: productType,
				} );
			},
			*removeFromList() {
				const { key } = getContext< ItemContext >();
				yield import( '@woocommerce/stores/woocommerce/cart' );
				const { state: wooState } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);
				yield savedListRequest(
					wooState,
					`/wc/store/v1/saved-lists/save-for-later/items/${ encodeURIComponent(
						key
					) }`,
					'DELETE'
				);
				yield refreshServerRegions();
			},
		},
	},
	{ lock: universalLock }
);

// Bridge the React → iAPI boundary: the cart block's React `cart-line-item-row` dispatches
// this event after the /saved-lists POST succeeds, because it can't import
// `@wordpress/interactivity` from its bundle. This listener drives the same state mutation
// that `addItemToList` would perform when called directly.
window.addEventListener(
	'wc-blocks_save_for_later_added',
	( event: Event ) => {
		const detail = ( event as CustomEvent< SavedListItem > ).detail;
		if ( ! detail?.product_id ) {
			return;
		}
		const variationMap: Record< string, string > = {};
		for ( const entry of detail.variation ?? [] ) {
			variationMap[ entry.attribute ] = entry.value;
		}
		if ( ! sflState.items ) {
			sflState.items = {};
		}
		sflState.items[ detail.product_id ] = {
			key: detail.key,
			productId: detail.product_id,
			variationId: detail.variation_id,
			quantity: detail.quantity,
			variation: variationMap,
		};

		// Kick off a server re-fetch so the Saved for Later Product Collection picks up the
		// newly saved item. Fire-and-forget: the cart block's React handler continues with its
		// optimistic cart removal in parallel, and `refreshServerRegions` coalesces concurrent
		// mutations into a single navigate() when appropriate.
		void refreshServerRegions();
	}
);
