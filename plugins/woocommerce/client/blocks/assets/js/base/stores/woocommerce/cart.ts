/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	CartResponseTotals,
	Currency,
} from '@woocommerce/types';
import type {
	Store as StoreNotices,
	Notice,
} from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';
import {
	createMutationQueue,
	MutationRequest,
	type MutationQueue,
	type MutationResult,
	type CycleSettleResult,
} from './mutation-batcher';
import { doesCartItemMatchAttributes } from '../../utils/variations/does-cart-item-match-attributes';

export type WooCommerceConfig = {
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
	nonOptimisticProperties?: string[];
	/**
	 * REST API base URL. Seeded server-side via
	 * `wp_interactivity_config( 'woocommerce' )`. Infra, not commerce state.
	 */
	restUrl?: string;
	/**
	 * Bootstrap Store API nonce. Seeded server-side (optional). The live nonce
	 * is refreshed from the `Nonce` response header on every request — see the
	 * `currentNonce` module variable below.
	 */
	nonce?: string;
	/**
	 * Map of Store API error codes to user-friendly messages. Infra config,
	 * not commerce state.
	 */
	errorMessages?: {
		[ key: string ]: string;
	};
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

export type ClientCartItem = Omit<
	OptimisticCartItem,
	'variation' | 'quantity'
> & {
	variation?: SelectedAttributes[];
	/** The target quantity (absolute). Either this or quantityToAdd must be provided. */
	quantity?: number;
	/** Optional: add this delta to current quantity instead of setting absolute quantity */
	quantityToAdd?: number;
};

type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

export type Store = {
	state: {
		findItemInCart: ( args: {
			id: ClientCartItem[ 'id' ];
			key?: ClientCartItem[ 'key' ];
			variation?: ClientCartItem[ 'variation' ];
		} ) => CartItem | OptimisticCartItem | undefined;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => Promise< void >;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => Promise< void >;
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => Promise< void >;
		waitForIdle: () => Promise< void >;
		showNoticeError: ( error: Error | ApiErrorResponse ) => Promise< void >;
		updateNotices: (
			notices: Notice[],
			removeOthers?: boolean
		) => Promise< void >;
	};
};

type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

// Guard to distinguish between optimistic and cart items.
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return 'name' in item;
}

function isApiErrorResponse(
	res: Response,
	json: unknown
): json is ApiErrorResponse {
	return ! res.ok;
}

function generateError( error: ApiErrorResponse ): Error {
	return Object.assign( new Error( error.message || 'Unknown error.' ), {
		code: error.code || 'unknown_error',
	} );
}

const generateErrorNotice = ( error: Error | ApiErrorResponse ): Notice => ( {
	notice: error.message,
	type: 'error',
	dismissible: true,
} );

const generateInfoNotice = ( message: string ): Notice => ( {
	notice: message,
	type: 'notice',
	dismissible: true,
} );

const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	// Items auto-removed by the server (stock change, product deleted, etc.).
	// We pass the optimistic snapshot as oldCart, so user-initiated removals
	// are already absent and do not generate spurious notices here.
	const autoDeletedToNotify = oldItems.filter(
		( old ) =>
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key )
	);

	// Items whose quantity was adjusted by the server (stock cap, sold-individually).
	// Comparing optimistic → server means intentional user changes are already
	// reflected in oldItems and will not trigger this notice.
	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		const old = oldItems.find( ( o ) => o.key === item.key );
		return old && item.quantity !== old.quantity;
	} );
	return [
		...autoDeletedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'"%s" was removed from your cart.'.replace( '%s', item.name )
			)
		),
		...autoUpdatedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'The quantity of "%1$s" was changed to %2$d.'
					.replace( '%1$s', item.name )
					.replace( '%2$d', item.quantity.toString() )
			)
		),
	];
};

let pendingRefresh = false;
let refreshTimeout = 3000;
let resolveNonceReady: ( () => void ) | null = null;
const isNonceReady = new Promise< void >( ( resolve ) => {
	resolveNonceReady = resolve;
} );

/**
 * Infra config (restUrl, nonce, errorMessages) lives in
 * `wp_interactivity_config( 'woocommerce' )`, not in reactive store state.
 */
const wooConfig = getConfig( 'woocommerce' ) as WooCommerceConfig;

/**
 * REST API base URL. Read once from config — it never changes at runtime.
 */
const restUrl = wooConfig.restUrl ?? '';

/**
 * Live Store API nonce.
 *
 * Config is read-only/static, but the nonce is refreshed per request from the
 * `Nonce` response header (rotating nonces). We therefore bootstrap it from
 * config and keep the current value in this module-level variable, updating it
 * whenever a response carries a fresh `Nonce` header (see `refreshNonce`). This
 * preserves the previous behavior where the refreshed nonce was written back
 * into `state.nonce`, without keeping it in reactive state.
 */
let currentNonce = wooConfig.nonce ?? '';

/**
 * Update the in-memory nonce from a response's `Nonce` header, if present.
 *
 * @param response Fetch response whose headers may carry a fresh nonce.
 */
function refreshNonce( response: Response ): void {
	currentNonce = response.headers.get( 'Nonce' ) || currentNonce;
}

function emitSyncEvent( {
	quantityChanges,
}: {
	quantityChanges: QuantityChanges;
} ) {
	window.dispatchEvent(
		new CustomEvent( 'wc-blocks_store_sync_required', {
			detail: {
				type: 'from_iAPI',
				quantityChanges,
			},
		} )
	);
}

/**
 * Cycle accumulator.
 *
 * The mutation batcher fires `onCycleSettle` ONCE when the queue returns to
 * idle, regardless of how many requests the cycle contained. Sync/legacy events
 * and store notices are cycle-level concerns (issues #63333 and #63560), so the
 * cart actions no longer fire them per request; instead each request contributes
 * to this per-cycle accumulator, and `handleCartCycleSettle` consumes it once.
 *
 * This is reset at the end of every settle cycle (see `handleCartCycleSettle`).
 */
type CartCycleAccumulator = {
	/** Aggregated quantity changes across every request in the cycle. */
	quantityChanges: QuantityChanges;
	/**
	 * True when the cycle contained at least one add/update request. Controls
	 * the legacy `wc-blocks_added_to_cart` event and the a11y announcement,
	 * which a pure removal cycle must not fire (preserves prior behavior where
	 * `removeCartItem` never triggered them).
	 */
	didAdd: boolean;
	/**
	 * Whether the cycle's info/error notice pass should run. Per-request
	 * opt-outs aggregate with logical AND: if ANY request in the cycle passes
	 * `showCartUpdatesNotices: false`, the whole cycle's notice pass is
	 * suppressed. A caller opting out is asserting "don't surface generic cart
	 * notices for my operation"; honoring that matches the old
	 * `batchAddCartItems`, where a single flag governed the entire batch.
	 * `removeCartItem` has no opt-out, so it always leaves this `true`.
	 */
	showNotices: boolean;
	/**
	 * Cart state captured after the last optimistic update in the cycle, used
	 * as the "old cart" for notice extraction. We deliberately compare the
	 * post-optimistic cart (not the batcher's pre-optimistic snapshot) against
	 * the final server state so that user-initiated removals/quantity changes
	 * are already reflected and do not generate spurious auto-change notices —
	 * see `getInfoNoticesFromCartUpdates`.
	 */
	optimisticSnapshot: Store[ 'state' ][ 'cart' ] | null;
};

function createCartCycleAccumulator(): CartCycleAccumulator {
	return {
		quantityChanges: {},
		didAdd: false,
		showNotices: true,
		optimisticSnapshot: null,
	};
}

let cartCycle: CartCycleAccumulator = createCartCycleAccumulator();

/**
 * Merge a single request's contribution into the current cycle accumulator.
 *
 * Called from each action at submit time (synchronously, before the batch is
 * sent), so that by settle time the accumulator reflects every request.
 */
function accumulateCartCycle( contribution: {
	quantityChanges?: QuantityChanges;
	didAdd?: boolean;
	showNotices?: boolean;
} ): void {
	const { quantityChanges, didAdd, showNotices } = contribution;

	if ( quantityChanges?.cartItemsPendingQuantity ) {
		cartCycle.quantityChanges.cartItemsPendingQuantity = [
			...( cartCycle.quantityChanges.cartItemsPendingQuantity ?? [] ),
			...quantityChanges.cartItemsPendingQuantity,
		];
	}
	if ( quantityChanges?.cartItemsPendingDelete ) {
		cartCycle.quantityChanges.cartItemsPendingDelete = [
			...( cartCycle.quantityChanges.cartItemsPendingDelete ?? [] ),
			...quantityChanges.cartItemsPendingDelete,
		];
	}
	if ( quantityChanges?.productsPendingAdd ) {
		cartCycle.quantityChanges.productsPendingAdd = [
			...( cartCycle.quantityChanges.productsPendingAdd ?? [] ),
			...quantityChanges.productsPendingAdd,
		];
	}
	if ( didAdd ) {
		cartCycle.didAdd = true;
	}
	// AND semantics: a single opt-out suppresses the whole cycle's pass.
	if ( showNotices === false ) {
		cartCycle.showNotices = false;
	}
}

/**
 * Announce the "added to cart" text to screen readers, if configured.
 */
async function announceAddedToCart(): Promise< void > {
	const { messages } = getConfig( 'woocommerce' ) as WooCommerceConfig;
	if ( messages?.addedToCartText ) {
		const { speak } = await import( '@wordpress/a11y' );
		speak( messages.addedToCartText, 'polite' );
	}
}

/**
 * Cycle-level settle handler wired into the batcher via `onCycleSettle`.
 *
 * Fires exactly once per settle cycle, synchronously during `reconcile()` while
 * the batcher is still `isProcessing` — so events and notices complete before
 * `refreshCartItems` (which bails while processing) can run, preserving the
 * no-race-with-refreshes guarantee that the old per-request `onSettled`
 * callbacks provided.
 */
function handleCartCycleSettle( result: CycleSettleResult< Cart > ): void {
	const cycle = cartCycle;
	// Reset immediately so a nested cycle (should not happen synchronously, but
	// defensively) starts clean and this handler is idempotent per cycle.
	cartCycle = createCartCycleAccumulator();

	if ( result.hasSuccess ) {
		// Legacy event + screen-reader announcement only for add/update cycles.
		if ( cycle.didAdd ) {
			triggerAddedToCartEvent( { preserveCartData: true } );
			announceAddedToCart();
		}

		// Sync event carries the whole cycle's aggregated quantity changes.
		emitSyncEvent( { quantityChanges: cycle.quantityChanges } );

		// Info + server-error notices: compare the post-optimistic cart against
		// the final server state, once for the whole cycle.
		const cart = result.data;
		if ( cycle.showNotices && cart && cycle.optimisticSnapshot ) {
			const infoNotices = getInfoNoticesFromCartUpdates(
				cycle.optimisticSnapshot,
				cart
			);
			const errorNotices = ( cart.errors ?? [] ).map(
				generateErrorNotice
			);
			actions.updateNotices( [ ...infoNotices, ...errorNotices ], true );
		}
	}
}

/**
 * Cart request queue singleton
 *
 * Lazily initialized on first use since state isn't available at module load.
 * Queues cart requests and handles optimistic updates and reconciliation.
 */
let cartQueue: MutationQueue< Cart > | null = null;

/**
 * Send a cart request through the queue.
 *
 * Handles optimistic updates, request queuing, and state reconciliation.
 */
async function sendCartRequest(
	stateRef: Store[ 'state' ],
	options: MutationRequest< Cart >
): Promise< MutationResult< Cart > > {
	await isNonceReady;
	// Lazily initialize queue on first use.
	if ( ! cartQueue ) {
		cartQueue = createMutationQueue< Cart >( {
			endpoint: `${ restUrl }wc/store/v1/batch`,
			getHeaders: () => ( {
				Nonce: currentNonce,
			} ),
			takeSnapshot: () => JSON.parse( JSON.stringify( stateRef.cart ) ),
			rollback: ( snapshot ) => {
				stateRef.cart = snapshot;
			},
			commit: ( serverState ) => {
				stateRef.cart = serverState;
			},
			// Batcher owns firing sync/legacy events and running the notice
			// pass once per settle cycle (issues #63333 and #63560). Runs while
			// the queue is still processing, so refreshes stay guarded.
			onCycleSettle: handleCartCycleSettle,
			fetchHandler: async ( ...args ) => {
				const response = await fetch( ...args );
				refreshNonce( response );
				return response;
			},
		} );
	}

	return cartQueue.submit( options );
}
// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// The cart store now lives under the `woocommerce/cart` namespace. The bare
// `woocommerce` namespace holds only the shared context and
// `wp_interactivity_config` (see the alias at the bottom of this file).
//
// Todo: export this store once the store is public.
const { state } = store< Store >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);
const { actions } = store< Store >(
	'woocommerce/cart',
	{
		state: {
			findItemInCart( {
				id,
				key,
				variation,
			}: {
				id: ClientCartItem[ 'id' ];
				key?: ClientCartItem[ 'key' ];
				variation?: ClientCartItem[ 'variation' ];
			} ) {
				return state.cart.items.find( ( cartItem ) => {
					if ( key ) {
						return key === cartItem.key;
					}
					if ( cartItem.type === 'variation' ) {
						if (
							id !== cartItem.id ||
							! cartItem.variation ||
							! variation ||
							cartItem.variation.length !== variation.length
						) {
							return false;
						}
						return doesCartItemMatchAttributes(
							cartItem,
							variation
						);
					}
					return id === cartItem.id;
				} );
			},
		},
		actions: {
			*removeCartItem( key: string ): AsyncAction< void > {
				// Contribute this request's changes to the cycle. Sync events
				// and notices are fired once per settle cycle by the batcher's
				// onCycleSettle handler (handleCartCycleSettle) — a removal is
				// not an "add", so it must not trigger the legacy event / a11y.
				accumulateCartCycle( {
					quantityChanges: { cartItemsPendingDelete: [ key ] },
					didAdd: false,
				} );

				try {
					yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/remove-item',
						method: 'POST',
						body: { key },
						applyOptimistic: () => {
							state.cart.items = state.cart.items.filter(
								( item ) => item.key !== key
							);
							// Capture post-optimistic cart for the cycle's
							// notice comparison (last writer in the cycle wins).
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} );
				} catch ( error ) {
					actions.showNoticeError( error as Error );
				}
			},

			*addCartItem(
				{ id, key, quantity, quantityToAdd, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< void > {
				if ( quantity !== undefined && quantityToAdd !== undefined ) {
					throw new Error(
						'addCartItem: pass either quantity or quantityToAdd, not both.'
					);
				}

				// Find existing item
				const existingItem = state.findItemInCart( {
					id,
					key,
					variation,
				} );

				// Determine the target quantity.
				// If quantityToAdd is provided, calculate target based on current
				// cart state (which includes optimistic updates from previous clicks).
				// This ensures rapid clicks compound correctly.
				let targetQuantity: number;
				if ( typeof quantityToAdd === 'number' ) {
					const currentQuantity = existingItem?.quantity ?? 0;
					targetQuantity = currentQuantity + quantityToAdd;
				} else if ( typeof quantity === 'number' ) {
					targetQuantity = quantity;
				} else {
					// Neither provided - default to 1
					targetQuantity = 1;
				}

				// Only treat as update if the item has a key (server-confirmed item).
				// Optimistic items don't have keys, so we should add them instead.
				const isUpdate = !! existingItem?.key;
				const endpoint = isUpdate ? 'update-item' : 'add-item';

				// Track what changes we're making for the aggregated sync event.
				const quantityChanges: QuantityChanges = isUpdate
					? {
							cartItemsPendingQuantity: existingItem?.key
								? [ existingItem.key ]
								: [],
					  }
					: { productsPendingAdd: [ id ] };

				// Contribute to the cycle. The batcher fires the legacy event,
				// a11y announcement, sync event and notice pass once when the
				// queue settles (handleCartCycleSettle). This is an add/update,
				// so it flips the cycle's didAdd flag; `showCartUpdatesNotices`
				// aggregates with AND across the cycle.
				accumulateCartCycle( {
					quantityChanges,
					didAdd: true,
					showNotices: showCartUpdatesNotices,
				} );

				// Prepare the item to send.
				let itemToSend: OptimisticCartItem;
				if ( isUpdate && existingItem ) {
					// Server-confirmed item: include the key for update-item endpoint.
					itemToSend = { ...existingItem, quantity: targetQuantity };
				} else {
					// New item or optimistic item: build fresh for add-item endpoint.
					// For optimistic items (existingItem without key), calculate delta
					// since add-item adds to existing quantity, not sets it.
					const quantityToSend = existingItem
						? targetQuantity - existingItem.quantity
						: targetQuantity;

					itemToSend = {
						id,
						quantity: quantityToSend,
						...( variation && { variation } ),
					} as OptimisticCartItem;
				}

				try {
					yield sendCartRequest( state, {
						path: `/wc/store/v1/cart/${ endpoint }`,
						method: 'POST',
						body: itemToSend,
						applyOptimistic: () => {
							if ( existingItem ) {
								// Update existing item's quantity (whether server-confirmed or optimistic).
								const isSoldIndividually =
									isCartItem( existingItem ) &&
									existingItem.sold_individually;
								if ( ! isSoldIndividually ) {
									existingItem.quantity = targetQuantity;
								}
							} else {
								// No existing item: push new optimistic item.
								state.cart.items.push( itemToSend );
							}
							// Capture post-optimistic cart for the cycle's
							// notice comparison (last writer in the cycle wins).
							cartCycle.optimisticSnapshot = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
					} );
				} catch ( error ) {
					// Show error notice
					actions.showNoticeError( error as Error );
				}
			},

			*refreshCartItems(): AsyncAction< void > {
				// Skip if queue is processing - it will apply server state when done
				if ( cartQueue?.getStatus().isProcessing ) {
					return;
				}

				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res = ( yield fetch( `${ restUrl }wc/store/v1/cart`, {
						method: 'GET',
						cache: 'no-store',
						headers: { 'Content-Type': 'application/json' },
					} ) ) as TypeYield< typeof fetch >;

					// Extract fresh nonce from response headers.
					refreshNonce( res );

					if ( resolveNonceReady ) {
						resolveNonceReady();
						resolveNonceReady = null;
					}

					const json = ( yield res.json() ) as Cart;

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					// If the batcher started a cycle while we were fetching,
					// discard this response — the batcher will reconcile.
					if ( cartQueue?.getStatus().isProcessing ) {
						return;
					}

					// Updates the local cart.
					state.cart = json;

					// Resets the timeout.
					refreshTimeout = 3000;
				} catch ( error ) {
					// Tries again after the timeout.
					setTimeout( actions.refreshCartItems, refreshTimeout );

					// Increases the timeout exponentially.
					refreshTimeout *= 2;
				} finally {
					pendingRefresh = false;
				}
			},

			*waitForIdle(): AsyncAction< void > {
				if ( cartQueue ) {
					yield cartQueue.waitForIdle();
				}
			},

			*showNoticeError(
				error: Error | ApiErrorResponse
			): AsyncAction< void > {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { actions: noticeActions } = store< StoreNotices >(
					'woocommerce/store-notices',
					{},
					{
						lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
					}
				);

				const { code, message } = error as ApiErrorResponse;

				const userFriendlyMessage =
					wooConfig.errorMessages?.[ code ] || message;

				// Todo: Check what should happen if the notice is already displayed.
				noticeActions.addNotice( {
					notice: userFriendlyMessage,
					type: 'error',
					dismissible: true,
				} );

				// Emmits console.error for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( error );
			},

			*updateNotices(
				newNotices: Notice[] = [],
				removeOthers = false
			): AsyncAction< void > {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { state: noticeState, actions: noticeActions } =
					store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{
							lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
						}
					);

				// Todo: Check what should happen if the notice is already displayed.
				const noticeIds = newNotices.map( ( notice ) =>
					noticeActions.addNotice( notice )
				);

				const { notices } = noticeState;
				if ( removeOthers ) {
					notices
						.map( ( { id } ) => id )
						.filter( ( id ) => ! noticeIds.includes( id ) )
						.forEach( ( id ) => noticeActions.removeNotice( id ) );
				}
			},
		},
	},
	{ lock: universalLock }
);

/**
 * Backwards-compatibility alias on the bare `woocommerce` namespace.
 *
 * The cart store moved to `woocommerce/cart` (T2 of the shared-stores plan).
 * Existing consumers (mini-cart, product button, add-to-cart-with-options,
 * the wishlist family) still call `store( 'woocommerce' )` and read
 * `state.cart`, `state.findItemInCart` and the cart `actions`. This alias keeps
 * them working unchanged until each consumer migrates in its own task; it is
 * removed in T10.
 *
 * Why this is safe with the iAPI `store()` merge/lock semantics:
 *
 * - We expose the *same underlying* `actions` and `findItemInCart` **function
 *   references**. iAPI's `deepMerge` copies function/primitive properties by
 *   descriptor, so the alias shares the identical functions — a single
 *   implementation, no divergent copies. Those functions close over the
 *   module-level `state` (the `woocommerce/cart` proxy), so they operate on the
 *   real cart state regardless of which namespace invoked them.
 * - `cart` is exposed as a **getter** that reads `state.cart` live. iAPI would
 *   otherwise deep-*copy* a plain-object `cart` into a separate `woocommerce`
 *   proxy (breaking cross-namespace reactivity); a getter is copied by
 *   descriptor and re-reads the single reactive source on every access, so
 *   consumers reading `wooState.cart` stay in sync with mutations applied to
 *   `woocommerce/cart`.
 * - Both stores are created with the same `universalLock`, so the alias
 *   registration passes the lock check and no store is unlocked/published.
 */
store< Store >(
	'woocommerce',
	{
		state: {
			get cart() {
				return state.cart;
			},
			findItemInCart: state.findItemInCart,
		},
		actions,
	},
	{ lock: universalLock }
);

// Trigger initial cart refresh.
actions.refreshCartItems();

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			actions.refreshCartItems();
		}
	}
);
