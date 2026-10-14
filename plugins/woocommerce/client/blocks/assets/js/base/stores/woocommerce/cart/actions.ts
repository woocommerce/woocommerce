/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type {
	Cart,
	CartVariationItem,
	ApiErrorResponse,
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
	computeKeylessAddSuppressKeys,
	generateErrorNotice,
	getInfoNoticesFromCartUpdates,
	isCartItem,
	lineMatchesProduct,
} from './notices';
import type { SelectedAttributes, WooCommerceConfig } from '../types';
import type {
	AddCartItemOutcome,
	AddCartItemPayload,
	CartUpdateOptions,
	OptimisticCartItem,
	Store,
} from './types';
import { doesCartItemMatchAttributes } from '../../../utils/variations/does-cart-item-match-attributes';

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
	 * Whether this mutation was issued by an add-style action (`addCartItem`,
	 * `updateCartItem` or `batchAddCartItems`) or by `removeCartItem`. Only
	 * `'add'`-origin successes trigger the legacy added-to-cart event and the
	 * screen-reader announcement.
	 */
	origin: 'add' | 'remove';
};

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

/**
 * Computes the canonical product token for accumulating per-product totals
 * across a batch.
 *
 * The token is stable: simple items produce `"<id>"` and variation items
 * produce `"<id>|<attr1>=<val1>&..."` with attributes sorted alphabetically
 * by name so insertion order differences do not produce different tokens.
 *
 * @param id        The product id.
 * @param variation The variation attributes, if any.
 * @return A canonical string token that uniquely identifies this product.
 */
function productToken(
	id: number,
	variation?: CartVariationItem[] | SelectedAttributes[]
): string {
	if ( ! variation || variation.length === 0 ) {
		return String( id );
	}
	const attrs = [ ...variation ]
		.sort( ( a, b ) => a.attribute.localeCompare( b.attribute ) )
		.map( ( v ) => `${ v.attribute }=${ v.value }` )
		.join( '&' );
	return `${ id }|${ attrs }`;
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
 * the sync event's shape matches today's single-mutation payloads.
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
let speakFn: ( typeof import( '@wordpress/a11y' ) )[ 'speak' ] | null = null;

/**
 * The in-flight (or already-settled) `@wordpress/a11y` import kicked off by
 * {@link preloadA11y}, reused across calls so the module is only imported
 * once per page load.
 */
let a11yPromise: Promise< typeof import( '@wordpress/a11y' ) > | null = null;

/**
 * Kicks off (once) the dynamic import of `@wordpress/a11y` and stashes its
 * `speak` export into {@link speakFn} on resolution.
 *
 * Called at the start of `addCartItem`, `updateCartItem` and `batchAddCartItems` so the module
 * is already loading — often already resolved — by the time the mutation
 * cycle settles and needs to announce. A rejected import (e.g. a chunk-load
 * failure) is swallowed: it degrades the eventual announcement to a no-op
 * without affecting the mutation's own success/failure outcome.
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
	stateRef: Store[ 'state' ],
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

				// Preserve today's relative order: the legacy event fires
				// before the sync event.
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
// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Todo: export this store once the store is public.
const { state } = store< Store >( 'woocommerce', {}, { lock: universalLock } );
const { actions } = store< Store >(
	'woocommerce',
	{
		state: {
			findItemInCart( {
				id,
				key,
				variation,
			}: {
				id: number;
				key?: string | undefined;
				variation?: SelectedAttributes[] | undefined;
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
				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

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
							cartAfterOptimistic = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
						meta: {
							quantityChanges: {
								cartItemsPendingDelete: [ key ],
							},
							origin: 'remove',
						},
					} ) ) as TypeYield< typeof sendCartRequest >;

					// Show notices from server response.
					const cart = result.data as Cart;
					if ( cart && cartAfterOptimistic ) {
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}
				} catch ( error ) {
					void actions.showNoticeError( error as Error );
				}
			},

			/**
			 * Sets the quantity of the cart line with the given key.
			 *
			 * Posts `update-item` with the rendered line carrying the new
			 * quantity, or `{ key, quantity }` when no rendered line has that
			 * key. The rendered quantity changes at once unless the line is sold
			 * individually. A successful cycle fires the added-to-cart event and
			 * announces `addedToCartText`. A rejected request shows an error
			 * notice and the call still resolves.
			 *
			 * @param  args          The update.
			 * @param  args.key      The key of the line to update. Required.
			 * @param  args.quantity The new absolute quantity of the line.
			 * @throws {Error} When `key` is missing, before any request or change.
			 */
			*updateCartItem( {
				key,
				quantity,
			}: {
				key: string;
				quantity: number;
			} ): AsyncAction< void > {
				if ( ! key ) {
					throw new Error( 'updateCartItem: a key is required.' );
				}

				preloadA11y();

				const existingItem = state.cart.items.find(
					( item ) => item.key === key
				);

				// The rendered line echoed with the new quantity, so REST
				// filters see the same body as for any rendered line. A key with
				// no rendered line is posted bare.
				const body = existingItem
					? { ...existingItem, quantity }
					: { key, quantity };

				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

				try {
					const result = ( yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/update-item',
						method: 'POST',
						body,
						applyOptimistic: () => {
							const isSoldIndividually =
								!! existingItem &&
								isCartItem( existingItem ) &&
								existingItem.sold_individually;
							if ( existingItem && ! isSoldIndividually ) {
								existingItem.quantity = quantity;
							}
							cartAfterOptimistic = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
						meta: {
							quantityChanges: {
								cartItemsPendingQuantity: existingItem
									? [ key ]
									: [],
							},
							origin: 'add',
						},
					} ) ) as TypeYield< typeof sendCartRequest >;

					const cart = result.data as Cart;
					if ( cart && cartAfterOptimistic ) {
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}
				} catch ( error ) {
					void actions.showNoticeError( error as Error );
				}
			},

			/**
			 * Adds `quantity` units of a product to the cart.
			 *
			 * Always posts `add-item` with `{ id, quantity, variation? }`, where
			 * `quantity` is the amount to add (1 when omitted) whatever line
			 * already holds the product. The matching rendered line rises by
			 * that amount at once, so rapid calls compound. A successful cycle
			 * fires the added-to-cart event and announces `addedToCartText`.
			 * Never rejects on a request failure: the outcome carries it.
			 *
			 * @param payload                        The product to add.
			 * @param payload.id                     The product or variation id.
			 * @param payload.quantity               The amount to add. Defaults to 1.
			 * @param payload.variation              The selected attributes of a variation.
			 * @param options                        The call options.
			 * @param options.showCartUpdatesNotices Whether to show the notices for
			 *                                       the cart update. Defaults to true.
			 * @return `{ success: true }` once the request is accepted, or
			 *         `{ success: false, error }` when it is rejected.
			 */
			*addCartItem(
				{ id, quantity, variation }: AddCartItemPayload,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< AddCartItemOutcome > {
				preloadA11y();

				// Find existing item
				const existingItem = state.findItemInCart( { id, variation } );

				// The amount posted is always the caller's delta, never a
				// function of the matched line. The rendered quantity is the
				// current one (which includes optimistic updates from previous
				// clicks) plus that delta, so rapid clicks compound.
				const delta = quantity ?? 1;
				const targetQuantity = ( existingItem?.quantity ?? 0 ) + delta;

				// Track what changes we're making for notice comparison.
				const quantityChanges: QuantityChanges = {
					productsPendingAdd: [ id ],
				};

				// A fresh payload for the add-item endpoint. It never copies the
				// matched line's key, so the server owns cart-line identity.
				const itemToSend = {
					id,
					quantity: delta,
					...( variation && { variation } ),
				} as OptimisticCartItem;

				// Capture cart state after optimistic updates for notice comparison.
				let cartAfterOptimistic: typeof state.cart | null = null;

				// Per-product capture for the exact-add test. Sum, by value and
				// before the optimistic bump mutates `existingItem.quantity` in
				// place, the quantity and keys of every pre-existing line matching
				// this product (id + variation). A product can occupy several lines
				// (e.g. a meta line ordered before a standalone line), and the
				// per-product total verifies exactness even when the server grows
				// a different line than the one bumped optimistically.
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
					{
						id,
						variation,
						preAddTotal,
						deltaTotal: delta,
						preExistingKeys,
					},
				];

				// Captured at the request-settlement boundary (the line right
				// after the request-sending yield resolves/throws) and never
				// overwritten afterwards. A throw from later post-success
				// processing (notices) must not downgrade an already-captured
				// success.
				let outcome: AddCartItemOutcome | undefined;

				try {
					const result = ( yield sendCartRequest( state, {
						path: '/wc/store/v1/cart/add-item',
						method: 'POST',
						body: itemToSend,
						applyOptimistic: () => {
							if ( existingItem ) {
								// This in-place bump is render-only. It makes
								// the common re-add flicker-free, but it must
								// never feed back into the posted amount, which
								// is already fixed above as the delta. It may
								// bump a server-keyed line's rendered quantity
								// (the accepted, self-correcting meta-only blip
								// the server reconciles away).
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
							// Capture state after optimistic update.
							cartAfterOptimistic = JSON.parse(
								JSON.stringify( state.cart )
							);
						},
						meta: { quantityChanges, origin: 'add' },
					} ) ) as TypeYield< typeof sendCartRequest >;

					// The request settled successfully. Capture the outcome
					// immediately so any later throw in this block (notices
					// import rejection, response-shape assertion) cannot
					// downgrade it to a failure.
					outcome = { success: true };

					// Success - handle side effects that don't trigger refreshCart
					const cart = result.data as Cart;

					// Show notices if enabled
					if (
						showCartUpdatesNotices &&
						cart &&
						cartAfterOptimistic
					) {
						// Compute the suppression set: for each added product,
						// check whether the server total matches the pre-add total
						// plus the posted delta. If so, the add was exact and the
						// pre-existing line keys are suppressed in the notice diff.
						const suppressKeys = computeKeylessAddSuppressKeys(
							productCaptures,
							cart
						);
						const infoNotices = getInfoNoticesFromCartUpdates(
							cartAfterOptimistic,
							cart,
							suppressKeys
						);
						const errorNotices =
							cart.errors.map( generateErrorNotice );
						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}
				} catch ( error ) {
					// Show error notice
					void actions.showNoticeError( error as Error );

					// Only record a failure outcome if the request-settlement
					// boundary above did not already capture a success — a throw
					// after a successful request must not overwrite it.
					outcome ??= {
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

				return outcome as AddCartItemOutcome;
			},

			/**
			 * Adds several products to the cart in one cycle.
			 *
			 * Each item posts `add-item` with `{ id, quantity, variation? }`,
			 * where `quantity` is the amount to add (1 when omitted), with the
			 * same optimistic update and notices as `addCartItem`. A request
			 * failure shows an error notice and never rejects.
			 *
			 * @param items                          The products to add.
			 * @param options                        The call options.
			 * @param options.showCartUpdatesNotices Whether to show the notices for
			 *                                       the cart update. Defaults to true.
			 */
			*batchAddCartItems(
				items: AddCartItemPayload[],
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			): AsyncAction< void > {
				preloadA11y();

				try {
					// Per-product capture for the exact-add test, accumulated
					// across all batch items. The map key is a canonical product
					// token (productToken(id, variation)) so that multiple batch
					// items for the same product accumulate into one entry. The
					// capture runs synchronously in the .map() below, before
					// applyOptimistic runs (applyOptimistic is gated behind
					// `await isNonceReady` inside sendCartRequest, so every
					// existingItem.quantity read during the .map() sees the same
					// pre-add cart).
					type BatchProductCapture = {
						id: number;
						variation?:
							| CartVariationItem[]
							| SelectedAttributes[]
							| undefined;
						preAddTotal: number;
						deltaTotal: number;
						preExistingKeys: string[];
					};
					const batchProductCaptures = new Map<
						string,
						BatchProductCapture
					>();
					const promises = items.map( ( item ) => {
						const existingItem = state.findItemInCart( {
							id: item.id,
							variation: item.variation,
						} );

						// As in `addCartItem`, the amount posted is the item's
						// delta, never a function of the matched line.
						const delta = item.quantity ?? 1;
						const targetQuantity =
							( existingItem?.quantity ?? 0 ) + delta;

						// A fresh payload for the add-item endpoint that never
						// copies the matched line's key.
						const itemToSend = {
							id: item.id,
							quantity: delta,
							...( item.variation && {
								variation: item.variation,
							} ),
						} as OptimisticCartItem;
						const itemMeta: CartMutationMeta = {
							quantityChanges: {
								productsPendingAdd: [ item.id ],
							},
							origin: 'add',
						};

						// Accumulate the per-product capture for the exactness
						// test. On the first encounter of each product token,
						// sum all pre-add quantities across every matching line
						// and collect their keys — both captured as primitives
						// here, before applyOptimistic mutates the cart. On
						// subsequent encounters of the same product, add only
						// the posted delta to the running deltaTotal.
						const token = productToken( item.id, item.variation );
						const capture = batchProductCaptures.get( token );
						if ( capture ) {
							capture.deltaTotal += delta;
						} else {
							const preExistingKeys: string[] = [];
							let preAddTotal = 0;
							for ( const cartLine of state.cart.items ) {
								if (
									lineMatchesProduct(
										cartLine,
										item.id,
										item.variation
									)
								) {
									preAddTotal += cartLine.quantity;
									if ( cartLine.key ) {
										preExistingKeys.push( cartLine.key );
									}
								}
							}
							batchProductCaptures.set( token, {
								id: item.id,
								variation: item.variation,
								preAddTotal,
								deltaTotal: delta,
								preExistingKeys,
							} );
						}

						return sendCartRequest( state, {
							path: '/wc/store/v1/cart/add-item',
							method: 'POST',
							body: itemToSend,
							applyOptimistic: () => {
								if ( existingItem ) {
									// As in addCartItem, this in-place bump is
									// render-only and must never feed back into the
									// posted amount, which is already fixed above as
									// the delta.
									existingItem.quantity = targetQuantity;
								} else {
									state.cart.items.push( itemToSend );
								}
							},
							meta: itemMeta,
						} );
					} );

					// Capture cart state after optimistic updates for notices.
					const cartAfterOptimistic = JSON.parse(
						JSON.stringify( state.cart )
					);

					const results = ( yield Promise.allSettled(
						promises
					) ) as PromiseSettledResult< MutationResult< Cart > >[];

					// Find the last successful result for notices.
					const lastSuccess = [ ...results ]
						.reverse()
						.find(
							(
								r
							): r is PromiseFulfilledResult<
								MutationResult< Cart >
							> => r.status === 'fulfilled' && r.value.success
						);

					if ( lastSuccess ) {
						const cart = lastSuccess.value.data as Cart;

						if ( showCartUpdatesNotices ) {
							// Compute the suppression set from the accumulated
							// per-product captures. For each product, if the server
							// total equals preAddTotal + sum of posted deltas, the
							// add was exact and the pre-existing keys are suppressed.
							const suppressKeys = computeKeylessAddSuppressKeys(
								[ ...batchProductCaptures.values() ],
								cart
							);
							const infoNotices = getInfoNoticesFromCartUpdates(
								cartAfterOptimistic,
								cart,
								suppressKeys
							);
							const errorNotices =
								cart.errors.map( generateErrorNotice );
							yield actions.updateNotices(
								[ ...infoNotices, ...errorNotices ],
								true
							);
						}
					}

					// Show error notices for failed items.
					const errorNotices = results
						.filter(
							( r ): r is PromiseRejectedResult =>
								r.status === 'rejected'
						)
						.map( ( r ) =>
							generateErrorNotice( r.reason as ApiErrorResponse )
						);
					if ( errorNotices.length > 0 ) {
						yield actions.updateNotices( errorNotices );
					}
				} catch ( error ) {
					void actions.showNoticeError( error as Error );
				}
			},

			/**
			 * Replaces `state.cart` with a fresh GET of the Store API cart and
			 * adopts the response's nonce. Skips the request without `restUrl`,
			 * while a mutation cycle is running, or while another refresh is
			 * pending. A failed request is retried after 3 s, doubling each time.
			 */
			*refreshCart(): AsyncAction< void > {
				// Skip when the server did not provide the interactivity
				// state this store needs, e.g. when a block imports this
				// store without loading the shared cart state. Release
				// pending mutations so they fail visibly instead of
				// waiting forever for a nonce that will never arrive.
				if ( ! state.restUrl ) {
					if ( resolveNonceReady ) {
						resolveNonceReady();
						resolveNonceReady = null;
					}
					return;
				}

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
					const res = ( yield fetch(
						`${ state.restUrl }wc/store/v1/cart`,
						{
							method: 'GET',
							cache: 'no-store',
							headers: { 'Content-Type': 'application/json' },
						}
					) ) as TypeYield< typeof fetch >;

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
					setTimeout( actions.refreshCart, refreshTimeout );

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
		},
	},
	{ lock: universalLock }
);

// Trigger initial cart refresh.
void actions.refreshCart();

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			void actions.refreshCart();
		}
	}
);
