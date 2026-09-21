/**
 * External dependencies
 */
import { getConfig, getContext, withScope } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	CartResponseTotals,
	Currency,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';
import {
	createMutationQueue,
	MutationRequest,
	type CycleSettledEntry,
	type MutationQueue,
	type MutationResult,
} from './mutation-batcher';
import {
	isCartItem,
	generateError,
	isApiErrorResponse,
	generateErrorNotice,
	lineMatchesProduct,
	computeKeylessAddSuppressKeys,
	getInfoNoticesFromCartUpdates,
	showNoticeError,
	updateNotices,
} from './notices';
import type { TemplateVariationAttribute } from './catalog';
import type { ProductScopeContext } from './scope';

export type WooCommerceConfig = {
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
	nonOptimisticProperties?: string[];
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

/**
 * A cart line as stored in `state.cart.items` before the server confirms it:
 * either a fresh line the client pushed, or a matched line bumped in place.
 */
export type OptimisticCartItem = {
	key?: string | undefined;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

/**
 * The payload the four designed cart actions build for `addCartItem()`'s
 * no-payload form, and the shape a caller-supplied payload is expected to
 * follow. Extension props flow through to the Store API `add-item` endpoint
 * unchanged.
 */
export type AddCartItemPayload = {
	/** The product id. */
	id: number;
	/** The selected variation attributes, if any. */
	variation?: TemplateVariationAttribute[];
	/**
	 * The amount `add-item` adds to a matching existing line (or the new
	 * line's quantity when none matches). Defaults to `1` when omitted.
	 */
	quantity?: number;
	/** An extension prop, posted to the Store API as given. */
	[ extensionKey: string ]: unknown;
};

export type AddCartItemOptions = { showCartUpdatesNotices?: boolean };

/**
 * The failure detail carried by a rejected `AddCartItemOutcome`.
 */
export type AddCartItemError = {
	/**
	 * Server error code for a per-item rejection (e.g.
	 * `woocommerce_rest_product_out_of_stock`), or the batcher's
	 * `unknown_error` fallback. Absent on whole-batch/transport failures.
	 */
	code?: string;
	/** Human-readable failure description. Always non-empty. */
	message: string;
};

/**
 * The per-call outcome `addCartItem` resolves with, captured at the moment
 * its own request settles (accepted or rejected). A later throw in
 * post-success processing (notices) never downgrades an already-captured
 * success into a failure.
 */
export type AddCartItemOutcome =
	| { success: true }
	| { success: false; error: AddCartItemError };

/** `state.cart`, the cart plane's public surface. */
export type CartActionsState = {
	cart: Omit< Cart, 'items' > & {
		items: ( OptimisticCartItem | CartItem )[];
		totals: CartResponseTotals;
	};
};

/** The cart plane's actions: the four designed actions. */
export type CartActionsActions = {
	addCartItem: (
		payload?: AddCartItemPayload | Event,
		options?: AddCartItemOptions
	) => Promise< AddCartItemOutcome >;
	updateCartItem: ( args: {
		key: string;
		quantity: number;
	} ) => Promise< void >;
	removeCartItem: ( key: string ) => Promise< void >;
	refreshCart: () => Promise< void >;
};

type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

/**
 * The per-mutation metadata carried through the cart's mutation queue,
 * submitted with every request and aggregated by the queue's
 * `onCycleSettled` callback once a cycle finishes.
 */
type CartMutationMeta = {
	/** The quantity changes this single mutation contributes, if it succeeds. */
	quantityChanges: QuantityChanges;
	/**
	 * Whether this mutation was issued by an add-style action (`addCartItem`
	 * or `updateCartItem`, regardless of whether it hit the `add-item` or
	 * `update-item` endpoint) or by `removeCartItem`. Only `'add'`-origin
	 * successes trigger the legacy added-to-cart event and the
	 * screen-reader announcement.
	 */
	origin: 'add' | 'remove';
};

/**
 * The shape of the scope layer's records and reading-element envelope this
 * module needs for `addCartItem()`'s draft form: enough to read and clear a
 * scope's record without importing `scope.ts`'s own `ScopeState` type, which
 * would otherwise create a circular type reference back to this module.
 */
type ScopeAccess = {
	/** Every scope's client-only record, keyed by name (`scope.ts`). */
	productScopes: Record<
		string,
		{ draftCartItem?: Record< string, unknown > }
	>;
	/** The reading element's product scope envelope (`scope.ts`). */
	productScope: {
		productId: number;
		variation: TemplateVariationAttribute[];
		draftCartItem?: Record< string, unknown >;
	};
};

/**
 * The slice of the shared `woocommerce` state this module reads and writes:
 * its own `cart`, the scope layer's records (to build and clear an
 * `addCartItem()` draft), and `restUrl` / `nonce` / `errorMessages`, seeded
 * by PHP (`BlocksSharedState.php`) but not part of the module's own
 * published state type.
 */
type SharedState = CartActionsState &
	ScopeAccess & {
		restUrl: string;
		nonce: string;
		errorMessages?: Record< string, string >;
	};

/**
 * The one action this module re-invokes through the registered (rather than
 * the raw generator) reference: whatever `store()` hands back for
 * `refreshCart`, callable with no further assumption about its return value.
 */
type RegisteredRefreshCart = { refreshCart: () => unknown };

// Bound once, by `index.ts`, to the store's own returned state and actions
// references right after its single `store()` registration. This module
// never calls `store()` itself — see `bindCartState`'s docblock.
let state: SharedState;
let registeredActions: RegisteredRefreshCart;

/**
 * Binds the shared `woocommerce` state and the registered `refreshCart`
 * action this module reads and calls. Called once, by `index.ts`,
 * immediately after its own `store()` call returns; also triggers the
 * initial cart load and subscribes the `wc/store/cart` data-store sync
 * listener.
 *
 * @param sharedState The store's own returned state reference.
 * @param actions     The store's own returned, registered actions reference
 *                    (used only to re-invoke `refreshCart` through the
 *                    Interactivity runtime's own driving, from the retry
 *                    timeout and the page-sync event listener).
 */
export function bindCartState(
	sharedState: SharedState,
	actions: RegisteredRefreshCart
): void {
	state = sharedState;
	registeredActions = actions;

	// Trigger initial cart refresh.
	void registeredActions.refreshCart();

	window.addEventListener(
		'wc-blocks_store_sync_required',
		( event: Event ) => {
			const customEvent = event as CustomEvent< {
				type: string;
				id: number;
			} >;
			if ( customEvent.detail.type === 'from_@wordpress/data' ) {
				void registeredActions.refreshCart();
			}
		}
	);
}

/**
 * Merges the per-mutation `quantityChanges` records of every successful
 * mutation in a settled cycle into the single record dispatched with the
 * sync event.
 *
 * Each of the three keys (`productsPendingAdd`, `cartItemsPendingQuantity`,
 * `cartItemsPendingDelete`) is unioned independently across `list`, with
 * duplicates collapsed via `Set`. A key that no entry in `list` contributes
 * is left absent from the result rather than emitted as an empty array, so
 * the sync event keeps the shape of a single-mutation payload.
 *
 * @param list The `quantityChanges` records of every successful mutation in
 *             the cycle, one per mutation.
 * @return The unioned `quantityChanges` record.
 */
function mergeQuantityChanges( list: QuantityChanges[] ): QuantityChanges {
	const productsPendingAdd = new Set< number >();
	const cartItemsPendingQuantity = new Set< string >();
	const cartItemsPendingDelete = new Set< string >();

	for ( const entry of list ) {
		entry.productsPendingAdd?.forEach( ( id ) =>
			productsPendingAdd.add( id )
		);
		entry.cartItemsPendingQuantity?.forEach( ( key ) =>
			cartItemsPendingQuantity.add( key )
		);
		entry.cartItemsPendingDelete?.forEach( ( key ) =>
			cartItemsPendingDelete.add( key )
		);
	}

	const merged: QuantityChanges = {};
	if ( productsPendingAdd.size > 0 ) {
		merged.productsPendingAdd = [ ...productsPendingAdd ];
	}
	if ( cartItemsPendingQuantity.size > 0 ) {
		merged.cartItemsPendingQuantity = [ ...cartItemsPendingQuantity ];
	}
	if ( cartItemsPendingDelete.size > 0 ) {
		merged.cartItemsPendingDelete = [ ...cartItemsPendingDelete ];
	}
	return merged;
}

let pendingRefresh = false;
let refreshTimeout = 3000;
let resolveNonceReady: ( () => void ) | null = null;
const isNonceReady = new Promise< void >( ( resolve ) => {
	resolveNonceReady = resolve;
} );

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
 * The `speak` binding from `@wordpress/a11y`, populated once the module
 * import kicked off by {@link preloadA11y} resolves. `null` until then, or if
 * the import never resolves (e.g. a chunk-load failure).
 */
let speakFn: ( typeof import('@wordpress/a11y') )[ 'speak' ] | null = null;

/**
 * The in-flight (or already-settled) `@wordpress/a11y` import kicked off by
 * {@link preloadA11y}, reused across calls so the module is only imported
 * once per page load.
 */
let a11yPromise: Promise< typeof import('@wordpress/a11y') > | null = null;

/**
 * Kicks off (once) the dynamic import of `@wordpress/a11y` and stashes its
 * `speak` export into {@link speakFn} on resolution.
 *
 * Called at the start of every add-style action so the module is already
 * loading — often already resolved — by the time the mutation cycle settles
 * and needs to announce. A rejected import (e.g. a chunk-load failure) is
 * swallowed: it degrades the eventual announcement to a no-op without
 * affecting the mutation's own success/failure outcome.
 */
function preloadA11y(): void {
	if ( a11yPromise ) {
		return;
	}
	a11yPromise = import( '@wordpress/a11y' );
	a11yPromise
		.then( ( { speak } ) => {
			speakFn = speak;
		} )
		.catch( () => {
			// Swallowed: a failed a11y chunk load must not affect mutation
			// settlement. The eventual announcement attempt becomes a no-op.
		} );
}

/**
 * Cart request queue singleton
 *
 * Lazily initialized on first use since state isn't available at module load.
 * Queues cart requests and handles optimistic updates and reconciliation.
 */
let cartQueue: MutationQueue< Cart, CartMutationMeta > | null = null;

/**
 * Send a cart request through the queue.
 *
 * Handles optimistic updates, request queuing, and state reconciliation.
 */
async function sendCartRequest(
	stateRef: SharedState,
	options: MutationRequest< CartMutationMeta >
): Promise< MutationResult< Cart > > {
	await isNonceReady;
	// Lazily initialize queue on first use.
	if ( ! cartQueue ) {
		cartQueue = createMutationQueue< Cart, CartMutationMeta >( {
			endpoint: `${ stateRef.restUrl }wc/store/v1/batch`,
			getHeaders: () => ( {
				Nonce: stateRef.nonce,
			} ),
			takeSnapshot: () => JSON.parse( JSON.stringify( stateRef.cart ) ),
			rollback: ( snapshot ) => {
				stateRef.cart = snapshot;
			},
			commit: ( serverState ) => {
				stateRef.cart = serverState;
			},
			fetchHandler: async ( ...args ) => {
				const response = await fetch( ...args );
				stateRef.nonce =
					response.headers.get( 'Nonce' ) || stateRef.nonce;
				return response;
			},
			// The single place that emits the cart's cross-cutting side
			// effects (the sync event, the legacy added-to-cart event, and
			// the screen-reader announcement) once per settled cycle, no
			// matter how many mutations it batched together.
			onCycleSettled: ( settled ) => {
				const successful = settled.filter(
					(
						entry
					): entry is Required<
						CycleSettledEntry< CartMutationMeta >
					> => entry.success && entry.meta !== undefined
				);
				if ( successful.length === 0 ) {
					// Every mutation in the cycle failed: nothing succeeded,
					// so no sync event, legacy event, or announcement fires.
					return;
				}

				const anyAdd = successful.some(
					( entry ) => entry.meta.origin === 'add'
				);

				// The legacy event fires before the sync event, keeping
				// their relative order.
				if ( anyAdd ) {
					triggerAddedToCartEvent( { preserveCartData: true } );
				}

				emitSyncEvent( {
					quantityChanges: mergeQuantityChanges(
						successful.map(
							( entry ) => entry.meta.quantityChanges
						)
					),
				} );

				if ( anyAdd ) {
					const { messages } = getConfig(
						'woocommerce'
					) as WooCommerceConfig;
					const text = messages?.addedToCartText;
					if ( text ) {
						if ( speakFn ) {
							speakFn( text, 'polite' );
						} else if ( a11yPromise ) {
							a11yPromise
								.then( () => {
									speakFn?.( text, 'polite' );
								} )
								.catch( () => {
									// Swallowed: a failed a11y chunk load
									// degrades the announcement to a no-op.
								} );
						}
					}
				}
			},
		} );
	}

	return cartQueue.submit( options );
}

/**
 * Builds the failure half of an `AddCartItemOutcome` from a caught error.
 *
 * @param error The error a mutation's request rejected with.
 * @return The failure outcome.
 */
function buildFailureOutcome( error: unknown ): AddCartItemOutcome {
	return {
		success: false,
		error: {
			...( ( error as ApiErrorResponse )?.code && {
				code: ( error as ApiErrorResponse ).code,
			} ),
			message:
				( error instanceof Error && error.message ) ||
				String( error ) ||
				'Request failed',
		},
	};
}

/**
 * A one-slot mutable box for the post-optimistic cart snapshot an
 * `applyOptimistic` callback captures. A plain `let` reassigned only inside
 * that callback narrows to `never` at its later read (TypeScript does not
 * track a reassignment visible only inside a nested closure), so the
 * snapshot is held on an object property instead, which carries no such
 * narrowing.
 */
type CartSnapshotBox = { current: CartActionsState[ 'cart' ] | null };

/**
 * Sets one cart line's absolute quantity via the Store API's `update-item`
 * endpoint, resolving an `AddCartItemOutcome` even though `updateCartItem`
 * itself discards it.
 *
 * @param key      The cart line's key.
 * @param quantity The absolute quantity to set.
 * @return The mutation's outcome.
 */
function* performUpdate(
	key: string,
	quantity: number
): AsyncAction< AddCartItemOutcome > {
	const existingItem = state.cart.items.find( ( item ) => item.key === key );
	const quantityChanges: QuantityChanges = {
		cartItemsPendingQuantity: existingItem?.key ? [ existingItem.key ] : [],
	};
	const itemToSend = (
		existingItem ? { ...existingItem, quantity } : { key, quantity }
	) as OptimisticCartItem;

	const cartAfterOptimistic: CartSnapshotBox = { current: null };
	let outcome: AddCartItemOutcome | undefined;

	try {
		const result = ( yield sendCartRequest( state, {
			path: '/wc/store/v1/cart/update-item',
			method: 'POST',
			body: itemToSend,
			applyOptimistic: () => {
				if ( existingItem ) {
					const isSoldIndividually =
						isCartItem( existingItem ) &&
						existingItem.sold_individually;
					if ( ! isSoldIndividually ) {
						existingItem.quantity = quantity;
					}
				}
				cartAfterOptimistic.current = JSON.parse(
					JSON.stringify( state.cart )
				);
			},
			meta: { quantityChanges, origin: 'add' },
		} ) ) as TypeYield< typeof sendCartRequest >;

		outcome = { success: true };

		const cart = result.data as Cart;
		if ( cart && cartAfterOptimistic.current ) {
			const infoNotices = getInfoNoticesFromCartUpdates(
				cartAfterOptimistic.current.items,
				cart
			);
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield* updateNotices( [ ...infoNotices, ...errorNotices ], true );
		}
	} catch ( error ) {
		void withScope( showNoticeError )(
			error as Error,
			state.errorMessages
		);
		outcome ??= buildFailureOutcome( error );
	}

	return outcome as AddCartItemOutcome;
}

/**
 * Finds the cart line matching an id/key/variation triple: an exact `key`
 * match when given, otherwise the first line matching the product and
 * variation. Used by `performAdd`'s existing-line lookup, and by the scope
 * envelope's `cartItem` accessor (`scope.ts`), which imports it directly.
 *
 * @param args           The lookup.
 * @param args.id        The product id.
 * @param args.key       An exact cart-line key, if known.
 * @param args.variation The selected variation attributes, if any.
 * @return The matching cart line, or `undefined`.
 */
export function findCartLine( args: {
	id: number;
	key?: string | undefined;
	variation?: SelectedAttributes[] | undefined;
} ): CartItem | OptimisticCartItem | undefined {
	return state.cart.items.find( ( cartItem ) => {
		if ( args.key ) {
			return args.key === cartItem.key;
		}
		return lineMatchesProduct( cartItem, args.id, args.variation );
	} );
}

/**
 * Posts a payload to the Store API's `add-item` endpoint, bumping a matching
 * cart line (or pushing a new one) optimistically, and applies the same
 * notice and cross-cutting-effect machinery every mutation shares.
 *
 * `payload.quantity` (defaulting to `1`) is posted exactly as given: it is
 * the delta `add-item` adds to a matching line, never an absolute quantity.
 *
 * @param payload                The payload to post, as given.
 * @param showCartUpdatesNotices Whether a successful add may show the
 *                               auto-update/auto-removal info notices.
 * @return The mutation's outcome.
 */
function* performAdd(
	payload: AddCartItemPayload,
	showCartUpdatesNotices: boolean
): AsyncAction< AddCartItemOutcome > {
	const { id, variation } = payload;
	const delta = typeof payload.quantity === 'number' ? payload.quantity : 1;
	const existingItem = findCartLine( { id, variation } );

	const quantityChanges: QuantityChanges = { productsPendingAdd: [ id ] };

	// Per-product capture for the keyless-add exactness test. Sums all
	// pre-add quantities across every cart line matching this product (id +
	// variation) and collects their keys, both by value, before the
	// optimistic bump runs.
	const preExistingKeys: string[] = [];
	let preAddTotal = 0;
	for ( const cartLine of state.cart.items ) {
		if ( lineMatchesProduct( cartLine, id, variation ) ) {
			preAddTotal += cartLine.quantity;
			if ( cartLine.key ) {
				preExistingKeys.push( cartLine.key );
			}
		}
	}
	const productCaptures = [
		{ id, variation, preAddTotal, deltaTotal: delta, preExistingKeys },
	];

	const cartAfterOptimistic: CartSnapshotBox = { current: null };
	let outcome: AddCartItemOutcome | undefined;

	try {
		const result = ( yield sendCartRequest( state, {
			path: '/wc/store/v1/cart/add-item',
			method: 'POST',
			body: payload,
			applyOptimistic: () => {
				if ( existingItem ) {
					// This in-place bump is render-only. It must never feed
					// back into the posted amount, which is already fixed
					// above as the payload's own quantity.
					const isSoldIndividually =
						isCartItem( existingItem ) &&
						existingItem.sold_individually;
					if ( ! isSoldIndividually ) {
						existingItem.quantity = existingItem.quantity + delta;
					}
				} else {
					state.cart.items.push( {
						...payload,
						quantity: delta,
					} as OptimisticCartItem );
				}
				cartAfterOptimistic.current = JSON.parse(
					JSON.stringify( state.cart )
				);
			},
			meta: { quantityChanges, origin: 'add' },
		} ) ) as TypeYield< typeof sendCartRequest >;

		// The request settled successfully. Capture the outcome immediately
		// so any later throw in this block (notices import rejection,
		// response-shape assertion) cannot downgrade it to a failure.
		outcome = { success: true };

		const cart = result.data as Cart;
		if ( showCartUpdatesNotices && cart && cartAfterOptimistic.current ) {
			const suppressKeys = computeKeylessAddSuppressKeys(
				productCaptures,
				cart
			);
			const infoNotices = getInfoNoticesFromCartUpdates(
				cartAfterOptimistic.current.items,
				cart,
				suppressKeys
			);
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield* updateNotices( [ ...infoNotices, ...errorNotices ], true );
		}
	} catch ( error ) {
		void withScope( showNoticeError )(
			error as Error,
			state.errorMessages
		);
		// Only record a failure outcome if the request-settlement boundary
		// above did not already capture a success — a throw after a
		// successful request must not overwrite it.
		outcome ??= buildFailureOutcome( error );
	}

	return outcome as AddCartItemOutcome;
}

/**
 * Type guard for `addCartItem`'s directive-bound call shape: a DOM `Event`
 * (what the Interactivity runtime passes as the first argument when the
 * action is bound to an event listener). Guarded by `typeof Event` in case
 * this module ever runs where the global is unavailable.
 *
 * @param value The value `addCartItem` received as its first argument.
 * @return `true` when `value` is a DOM `Event`.
 */
function isEvent( value: unknown ): value is Event {
	return typeof Event !== 'undefined' && value instanceof Event;
}

/**
 * `addCartItem( payload?, options? )`. With no payload — or with a DOM
 * `Event` as the first argument, which is what a directive-bound call
 * passes — posts the reading element's product scope's `draftCartItem` and
 * removes that scope's record when the server accepts it. With a payload,
 * posts the payload as given and removes no record.
 *
 * Resolves `{ success: true }` or `{ success: false, error }` for every
 * outcome, including a server rejection or a transport failure, and never
 * rejects.
 *
 * @param payloadOrEvent                 The payload to post, `undefined`, or the
 *                                       DOM `Event` a directive-bound call passes.
 * @param options                        Call options.
 * @param options.showCartUpdatesNotices Whether a successful add may show the
 *                                       auto-update/auto-removal info notices.
 *                                       Defaults to `true`.
 * @return The mutation's outcome.
 */
function* addCartItem(
	payloadOrEvent?: AddCartItemPayload | Event,
	{ showCartUpdatesNotices = true }: AddCartItemOptions = {}
): AsyncAction< AddCartItemOutcome > {
	preloadA11y();

	// A real payload — not the no-payload/Event draft form.
	if ( payloadOrEvent !== undefined && ! isEvent( payloadOrEvent ) ) {
		return yield* performAdd( payloadOrEvent, showCartUpdatesNotices );
	}

	// The draft form: build the payload from the reading element's product
	// scope, then remove that scope's record when the add succeeds.
	const context = getContext< ProductScopeContext >( 'woocommerce' );
	const scopeName = context?.scopeName ?? '_default';
	const record = state.productScopes[ scopeName ]?.draftCartItem;
	const variation = state.productScope.variation;
	const draftQuantity = state.productScope.draftCartItem?.quantity as
		| number
		| undefined;
	const payload: AddCartItemPayload = {
		...record,
		id: state.productScope.productId,
		...( variation.length > 0 && { variation } ),
		quantity: draftQuantity ?? 1,
	};

	const outcome = yield* performAdd( payload, showCartUpdatesNotices );

	if ( outcome.success ) {
		delete state.productScopes[ scopeName ];
	}

	return outcome;
}

/**
 * `updateCartItem( { key, quantity } )` sets that cart line's absolute
 * quantity via the Store API's `update-item` endpoint.
 *
 * @param args          The line to update.
 * @param args.key      The cart line's key.
 * @param args.quantity The absolute quantity to set.
 */
function* updateCartItem( {
	key,
	quantity,
}: {
	key: string;
	quantity: number;
} ): AsyncAction< void > {
	yield* performUpdate( key, quantity );
}

/**
 * `removeCartItem( key )` removes the cart line immediately (optimistically)
 * and confirms the removal against the server.
 *
 * @param key The cart line's key.
 */
function* removeCartItem( key: string ): AsyncAction< void > {
	// Capture cart state after optimistic updates for notice comparison.
	const cartAfterOptimistic: CartSnapshotBox = { current: null };

	try {
		const result = ( yield sendCartRequest( state, {
			path: '/wc/store/v1/cart/remove-item',
			method: 'POST',
			body: { key },
			applyOptimistic: () => {
				state.cart.items = state.cart.items.filter(
					( item ) => item.key !== key
				);
				// Capture state after optimistic update.
				cartAfterOptimistic.current = JSON.parse(
					JSON.stringify( state.cart )
				);
			},
			meta: {
				quantityChanges: { cartItemsPendingDelete: [ key ] },
				origin: 'remove',
			},
		} ) ) as TypeYield< typeof sendCartRequest >;

		// Show notices from server response.
		const cart = result.data as Cart;
		if ( cart && cartAfterOptimistic.current ) {
			const infoNotices = getInfoNoticesFromCartUpdates(
				cartAfterOptimistic.current.items,
				cart
			);
			const errorNotices = cart.errors.map( generateErrorNotice );
			yield* updateNotices( [ ...infoNotices, ...errorNotices ], true );
		}
	} catch ( error ) {
		void withScope( showNoticeError )(
			error as Error,
			state.errorMessages
		);
	}
}

/**
 * `refreshCart()` re-reads the cart from the server. Skips a call while a
 * mutation cycle is processing (the cycle applies its own server state when
 * done) or while a previous refresh is still pending, and retries with a
 * growing delay when the fetch itself fails.
 */
function* refreshCart(): AsyncAction< void > {
	// Skip if queue is processing - it will apply server state when done
	if ( cartQueue?.getStatus().isProcessing ) {
		return;
	}

	// Skips if there's a pending request.
	if ( pendingRefresh ) {
		return;
	}

	pendingRefresh = true;

	try {
		const res = ( yield fetch( `${ state.restUrl }wc/store/v1/cart`, {
			method: 'GET',
			cache: 'no-store',
			headers: { 'Content-Type': 'application/json' },
		} ) ) as TypeYield< typeof fetch >;

		// Extract fresh nonce from response headers.
		state.nonce = res.headers.get( 'Nonce' ) || state.nonce;

		if ( resolveNonceReady ) {
			resolveNonceReady();
			resolveNonceReady = null;
		}

		const json = ( yield res.json() ) as Cart;

		// Checks if the response contains an error.
		if ( isApiErrorResponse( res, json ) ) {
			throw generateError( json );
		}

		// If the batcher started a cycle while we were fetching, discard
		// this response — the batcher will reconcile.
		if ( cartQueue?.getStatus().isProcessing ) {
			return;
		}

		// Updates the local cart.
		state.cart = json;

		// Resets the timeout.
		refreshTimeout = 3000;
	} catch {
		// Tries again after the timeout, through the registered action so
		// the Interactivity runtime drives the retry's own generator.
		setTimeout( () => {
			void registeredActions.refreshCart();
		}, refreshTimeout );

		// Increases the timeout exponentially.
		refreshTimeout *= 2;
	} finally {
		pendingRefresh = false;
	}
}

/** The cart plane's initial state, merged into the `woocommerce` store. */
export const cartActionsState: CartActionsState = {
	// `cart` carries no initial default: PHP always seeds it
	// (`BlocksSharedState.php`) before the client runs, and `refreshCart`
	// assigns it at load time otherwise.
} as CartActionsState;

/**
 * The cart plane's actions, merged into the `woocommerce` store. Left
 * untyped (rather than annotated as `CartActionsActions`, whose signatures
 * are the Promise-returning shape `store()` hands back to callers): these
 * are the raw generator implementations, and `store()`'s own generic
 * (`ConvertPromisesToGenerators`) is what checks them against
 * `CartActionsActions` when `index.ts` passes this object in.
 */
export const cartActions = {
	addCartItem,
	updateCartItem,
	removeCartItem,
	refreshCart,
};
